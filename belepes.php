<?php
include_once("./templates/header.tpl.php");
?><div class="szerkcard">
    <div class="szerkcardtitle">Bejelentkezés</div>
    <div class="szerkcardbody">
        <div class="contentcenter">
            <form action="./index.php" method="post">
                <div>
                    <label for="felhasznalonev">Felhasználónév:
                    <input type="text" accept-charset="utf-8" name="felhasznalonev" placeholder="Felhasználónév" id="felhasznalonev" required></input></label>
                </div>
                
                <div>
                    <label for="jelszo">Jelszó:
                    <input type="password" name="jelszo" placeholder="Jelszó" id="jelszo" required></label>
                </div>

                <div class="submit"><input type="submit" value="Bejelentkezés"></div>
            </form>
        </div>
    </div>
</div><?php
include_once("./templates/footer.tpl.php");
