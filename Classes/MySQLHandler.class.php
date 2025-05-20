<?php

class MySQLHandler
{
    public $last_insert_id = null;
    public $siker = false;
    public $sorokszama = 0;
    private $hibauzenet;
    private $hibakod;
    private $con;
    private $exception;
    private $keepalive;
    private $result;
    private $querystring = "";
    private $types = "";
    private $vartparam = 0;
    private $stmt;
    private $showdebug = DEBUG_MODE;
    private $params = array();
    private $queryruntime;

    public function __construct(string $query = null, ...$params)
	{
        if(isset($GLOBALS['felhasznaloid']) && $GLOBALS['felhasznaloid'] == 1)
        {
            $this->showdebug = true;
        }
        try
        {
            $this->con = mysqli_connect($GLOBALS['DATABASE_HOST'], $GLOBALS['DATABASE_USER'], $GLOBALS['DATABASE_PASS'], $GLOBALS['DATABASE_NAME']);
        }
        catch(Exception $e)
        {
            echo "<h2>Nem sikerült csatlakozni a MySQL kiszolgálóhoz!</h2>";
            echo $e->getMessage();
        }

        if($this->con)
        {
            mysqli_set_charset($this->con, "UTF8");
            $this->stmt = $this->con->stmt_init();
        }

        if($query)
        {
            $this->Query($query, ...$params);
        }
    } 

    public function __destruct()
    {
        if($this->con)
            mysqli_close($this->con);
    }

    public function ShowQueryDetails()
    {
        $params = "";
        foreach($this->params as $p)
        {
            $params .= $p . "; ";
        }
        $params = trim($params, ";");
        echo FormatSQL($this->querystring) . "<br><br>Paraméterek: " . $params . "<br><br>Query futásideje: " . round($this->queryruntime, 2) . " mp";
    }

    public function ShowException()
    {
        if($this->showdebug)
        {
            
            $message = "";
            if(!$this->con)
            {
                $message = "<h2>A meghívni kísérelt MySQL kapcsolat már lezárult!</h2>";
            }
            elseif(!$this->stmt)
            {
                $message = "<h2>Hibásan megírt SQL query!</h2>" .  $this->exception . "<br>" .  $this->querystring . "<br>Hibakód: " . $this->hibakod . ": " . $this->hibauzenet . "<br>";;
            }
            elseif(count($this->params) != strlen($this->types)
                || count($this->params) != $this->vartparam
                || strlen($this->types) != $this->vartparam
            )
            {
                $message = "<h2>Hibás paraméterszám!</h2>" . "Várt paraméter: " . $this->vartparam . "<br>Típusszám: " . strlen($this->types)
                    . "<br>Paraméterszám: " . count($this->params) . "<br>Lekérdezés:<br>" . FormatSQL($this->querystring) . "<br>";
            }
            elseif(!$this->querystring)
            {
                $message = "<h2>Nem adtál meg lekérdezést!</h2>";
            }
            elseif(!$this->siker)
            {
                $message = "<h2>A MySQL lekérdezésbe valamilyen hiba csúszott!</h2>" . mysqli_error($this->con);
            }
            else
            {   
                $message = "<h2>Ismeretlen hiba a MySQL lekérdezésben!</h2>";
            }
            try
            {
                throw new Exception($message);
            }
            catch(Exception $e)
            {
                echo $e;
            }
        }
        else
        {
            echo "<h2>A MySQL lekérdezésbe valamilyen hiba csúszott!</h2>";
        }
    }

    private function GetType($param)
    {
        if(is_int($param) || is_bool($param))
        {
            $this->types .= "i";
        }
        elseif(is_double($param) || is_float($param))
        {
            $this->types .= "d";
        }
        elseif(is_string($param) || is_null($param))
        {
            $this->types .= "s";
        }
        else
        {
            $this->types .= "b";
        }
    }

    private function SetTypes(...$params)
    {
        $this->types = "";
        if(count($params) > 0)
        {   
            foreach($params as $param)
            {
                $this->GetType($param);
            }
        }
    }

    public function Result()
    {
        return $this->result;
    }

    public function KeepAlive($keepalive = true)
    {
        $this->keepalive = $keepalive;
    }

    public function Fetch()
    {
        if($this->result)
        {
            return mysqli_fetch_assoc($this->result);
        }
        else
        {
            return false;
        }
    }

    public function Prepare(string $query)
    {
        $this->keepalive = true;
        $this->querystring = $query;
        if(isset($GLOBALS["querylist"]) && $GLOBALS["querylist"])
            $GLOBALS["querylist"][] = $query;
        $this->types = "";
        if($this->con)
        {
            try
            {
                $prep = $this->stmt->prepare($query);
            }
            catch(Exception $e)
            {
                $this->exception = $e->getMessage();
                $prep = false;
            }
            if(!$prep)
            {
                $this->hibauzenet = $this->con->error_list[0]['error'];
                $this->hibakod = $this->con->errno;
                $this->stmt = null;
                $this->siker = false;
            }
            else
            {
                $this->vartparam = $this->stmt->param_count;
                $this->siker = true;
            }
        }
        else
        {
            $this->ShowException();
        }
        return $this->siker;
    }

    public function Query(string $query, ...$params) : mysqli_result | false
    {
        if($this->Prepare($query))
            return $this->Run(...$params);
        else
        {
            $this->ShowException();
            return false;
        }
    }

    public function Run(...$params) : mysqli_result | false
    {
        $start_time = microtime(true);
        $paramcount = 0;
        $this->params = $params;
        if($this->stmt && $this->siker)
        {
            $this->siker = false;
            $paramcount = count($params);
            if(!$this->types)
                $this->SetTypes(...$params);

            if($paramcount == strlen($this->types) && $this->vartparam == $paramcount)
            {
                if($paramcount > 0)
                    $this->stmt->bind_param($this->types, ...$params);
                $this->stmt->execute();

                @$GLOBALS['dbcallcount']++;
                $this->last_insert_id = mysqli_insert_id($this->con);
                $this->result = $this->stmt->get_result();

                if(!is_bool($this->result))
                    $this->sorokszama = mysqli_num_rows($this->result);
                
                if($this->con && mysqli_errno($this->con) != 0)
                    $this->hibakod = mysqli_errno($this->con);
                else
                    $this->siker = true;
            }
    
            if(!$this->keepalive && $this->con)
            {
                @mysqli_close($this->con);
                $this->con = null;
            }
        }

        $this->queryruntime = microtime(true) - $start_time;
        
        if(!$this->siker)
            $this->ShowException();

        return $this->result;
    }

    public function Bind(&...$array)
    {
        $returnarr = array();
        $tobind = mysqli_fetch_assoc($this->result);
        if($tobind)
        {
            $dbelem = count($tobind);
            
            if($dbelem == count($array))
            {
                $i = 0;
                foreach($tobind as $key => $value)
                {
                    $array[$i] = $value;
                    $returnarr[$key] = $value;
                    $i++;
                }
                
                return $returnarr;
            }
            else
            {
                echo "<h2>Az SQL lekérdezés mezőinek, és a kötni kívánt tömb elemeinek száma különbözik!</h2>";
                echo "SQL adatbázisból vett mezők száma: . $dbelem";
                echo "A kötni kívánt elemek száma: " . count($array);
                return false;
            }
        }
        else
        {
            echo "<h2>A lekérdezés sikertelen!</h2>";
            return false;
        }
    }

    public function NaturalSort($column, $casesensitive = false, $result = null)
    {
        if(!$result)
        {
            $result = $this->result;
        }

        $result = $this->AsArray();
        usort($result, function($a, $b) use ($column, $casesensitive) {
            if($a[$column] == null)
            {
                $a[$column] = "zzzzz";
            }
    
            if($b[$column] == null)
            {
                $b[$column] = "zzzzz";
            }
    
            if($casesensitive)
            {
                return strnatcmp($a[$column], $b[$column]); //Case sensitive
            }
            else
            {
                return strnatcasecmp($a[$column],$b[$column]); //Case insensitive
            }
        });

        //echo json_encode($result);
        return $result;
    }

    public function AsArray($arrkey = null, $ondimensional = false)
    {
        if($arrkey || $ondimensional)
        {
            $returnarr = array();
            foreach($this->result as $sor)
            {
                if($ondimensional)
                {
                    foreach($sor as $value)
                    {
                        $returnarr[] = $value;
                        break;
                    }
                }
                
                if($arrkey)
                    $returnarr[$sor[$arrkey]] = $sor;
                else
                    $returnarr[] = $sor;
            }
        }
        else
        {
            $returnarr = mysqli_fetch_all($this->result, MYSQLI_ASSOC);
        }
        return $returnarr;
    }

    public function ToTable()
    {
        ?><table>
            <thead>
                <tr><?php
                    foreach($this->Fetch() as $key => $value)
                    {
                        ?><th><?=ucfirst($key)?></th><?php
                    }
                ?></tr>
            </thead>
            <tbody><?php
                foreach($this->Result() as $row)
                {
                    ?><tr><?php
                        foreach($row as $val)
                        {
                            ?><td><?=$val?></td><?php
                        }
                    ?></tr><?php
                }
            ?></tbody>
        </table><?php
    }

    public function Close($backtosender = null)
    {
        if($this->con)
            //mysqli_close($this->con);

        if($backtosender)
        {
            header("Location: $backtosender");
            die;
        }
    }
}