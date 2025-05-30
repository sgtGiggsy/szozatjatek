var currentid = cursor = 0;
var json;
var lastkey = null;
var jatekaktiv = true;
const betuszam = document.getElementById('jatektabla').getAttribute('data-betuszam');
const EgykarakteresBetukEsSzamok = [
    "A", "Á", "B", "C", "D", "E", "É", "F", "G", "H",
    "I", "Í", "J", "K", "L", "M", "N", "O", "Ó", "Ö", "Ő",
    "P", "Q", "R", "S", "T", "U", "Ú", "Ü", "Ű", "V", "W", "X", "Y", "Z",
    "0", "1", "2", "3", "4", "5", "6", "7", "8", "9"
];

// 0. elem = nincs találat, piros; 1. elem = találat rossz helyen, sárga; 2. elem találat jó helyen, zöld
const colors = [
    'var(--hianyzik)',
    'var(--rosszhely)',
    'var(--talalat)'
]

window.onload = function() {
    setRow();
    jumpToKey(0);
    if(typeof window.MentettKitoltes !== 'undefined')
        folytatKitoltes(MentettKitoltes);
}

document.getElementById('jatekter').addEventListener('click' , function (event) {
    jumpToKey(cursor);
});

document.getElementById('jatektabla').addEventListener('keydown', function (event) {
    let key = null;
    if(event.key)
        key = event.key.toUpperCase();
    else
        key = event.code.toUpperCase();

    //console.log("Key pressed: " + key);
    if (EgykarakteresBetukEsSzamok.includes(key))
        lastkey = key;
    else
        lastkey = null;

    if (event.key == 'Backspace')
        lastkey = 'Backspace';
});

document.getElementById('beKuld').addEventListener('keydown', function (event) {
    if (event.key == 'Backspace')
        backSpace();
});

function setCursor(id) {
    cursor = id;
    return cursor;
}

function setBekuld(allapot) {
    if(allapot) {
        for(let i = 0; i < betuszam; i++) {
            if(!document.getElementById('jatekmezoinput_' + currentid + '_' + i).value) {
                allapot = false;
                jumpToKey(setCursor(0));
            }
        }
    }

    // Megfordítom a bool értékét, mert nekem intuitívabb false-szal tiltani, mint true-val
    allapot = allapot ? false : true;
    document.getElementById("beKuld").disabled = allapot;
    // Itt false állapotra kell belépni, mert a disabled attribute false, tehát enabled állapotban van a gomb
    if(!allapot)
        document.getElementById("beKuld").focus();
}

function jumpToNext(id) {
    cursor = parseInt(id);
    if(lastkey && lastkey != 'Backspace')
    {
        // Ez azért van, hogy ha van is érték az adott mezőben, visszatörlés nélkül íródjon felül
        document.getElementById('jatekmezoinput_' + currentid + '_' + cursor).value = lastkey;
        lastkey = null;
        jumpToKey(++cursor);
    }
    else if(lastkey && lastkey == 'Backspace')
        backSpace();
}

function jumpToKey(id){
    if(jatekaktiv)
    {
        if(id < betuszam)
            document.getElementById('jatekmezoinput_' + currentid + '_' + id).focus();
        else
            setBekuld(true);
    }
}

function backSpace() {
    if(cursor >= betuszam)
    {
        cursor = betuszam - 1;
    }
    else
    {
        lastkey = null;
        let currfocus = document.getElementById('jatekmezoinput_' + currentid + "_" + cursor);
        currfocus.value = "";
        if(cursor > 0)
        {
            let previd = cursor - 1;
            let prevfocus = document.getElementById('jatekmezoinput_' + currentid + "_" + previd);
            prevfocus.value = "";
            cursor--;
        }
    }
    setBekuld(false);
    jumpToKey(cursor);
}

function billentyuLeut(key) {
    if(key == 'Backspace')
    {
        if(cursor >= betuszam)
        {
            let lastelem = betuszam - 1;
            document.getElementById('jatekmezoinput_' + currentid + "_" + lastelem).value = "";
        }
        backSpace();
    }
    else if(cursor < betuszam)
    {
        let currfocus = document.getElementById('jatekmezoinput_' + currentid + "_" + cursor);
        currfocus.value = key;
        lastkey = key;
        jumpToNext(cursor);
    }
}

function disableAllFields() {
    let tablazat = document.getElementById('jatektabla');
    let inputok = tablazat.getElementsByTagName('input');
    let inpszam = inputok.length;
    for(let i = 0; i < inpszam; i++)
        inputok[i].disabled = true;
}

function setRow() {
    for(let i = 0; i < betuszam; i++)
    {
        let input = document.getElementById('jatekmezoinput_' + currentid + "_" + i);
        input.value = "";
        input.disabled = false;
    }
    cursor = 0;
    setBekuld(false);
    jumpToKey(cursor);
}

function folytatKitoltes(mentett) {
    let sorokszama = mentett.length;
    for(let i = 0; i < sorokszama; i++)
    {
        currentid = i;
        let sor = mentett[i].valasz;
        for(let j = 0; j < betuszam; j++)
        {
            let input = document.getElementById('jatekmezoinput_' + i + "_" + j);
            input.value = sor[j];
            if(sor[j] != "")
                input.disabled = true;
        }
        sorSzinez(mentett[i].eredmeny);
    }
    // A következő sorra ugrunk
    currentid++;
    setRow();
}

function sorSzinez(eredmeny) {
    for(let i = 0; i < betuszam; i++)
    {
        let bevitelem = document.getElementById('jatekmezoinput_' + currentid + "_" + i);
        let billentyu = document.getElementById('bill-' + bevitelem.value.toLowerCase());
        bevitelem.disabled = true;

        if(eredmeny[i] > 0)
            document.getElementById('jatekmezo_' + currentid + "_" + i).style.backgroundColor = colors[eredmeny[i]];

        // Csak akkor változtatunk színt, ha még nincs háttérszín a billentyűn, vagy zöldre állítjuk, mivel csak a sárga->zöld átmenet legális
        if(!billentyu.style.backgroundColor || eredmeny[i] == 2)
            billentyu.style.backgroundColor = colors[eredmeny[i]]
    }
}

function eredmenyKiErtekel(rawjson) {
    json = rawjson.data;
    if(json.retcode < 200 || json.retcode > 204)
    {
        if(json.retcode == 423)
            disableAllFields();

        // A késleltetés nélkül nem kerül focus-ba az OK gomb a felugrón
        setTimeout(() => {
            Swal.fire({
                title: "HIBA!",
                text: json.uzenet,
                icon: "error",
            });
        }, 0);

        if(json.retcode == 406)
            setRow();
    }
    else
    {
        // Szinezzük a sorokat és betűket a virtuális billentyűzeten
        sorSzinez(json.eredmeny);

        if(json.retcode == 202 || json.retcode == 204)
        {
            disableAllFields();
            endgameSplash();
            setButton();
            jatekaktiv = false;
        }
        else
        {
            currentid++;
            cursor = 0;
            setRow();
            jumpToKey(cursor);
        }
        document.getElementById("beKuld").disabled = true;
    }
}

function copyGameTable() {
    const eredetiTabla = document.querySelector('table'); // vagy valamilyen ID: #myTable
    let masolat = eredetiTabla.cloneNode(true); // true = mély másolat (gyerekeivel együtt)
    masolat.id = 'masolat';
    masolat.querySelectorAll('td').forEach(cell => {
        cell.innerHTML = '&nbsp;';
    });
    let finalTable = "<div class='masolatwrap'>" + masolat.outerHTML + "</div>";
    document.getElementById('teszt').innerHTML = finalTable;
    masolat = document.getElementById('masolat');
    
    html2canvas(masolat).then(canvas => {
        // Kép megjelenítése az oldalon
        document.body.appendChild(canvas);

        // Kép letöltése PNG formátumban
        const link = document.createElement('a');
        link.download = 'tabla.png';
        link.href = canvas.toDataURL('image/png');
        link.click();
    });

    return finalTable;
    //document.body.appendChild(masolat); // vagy ahova akarod
}

function setButton() {
    document.getElementById('beKuld').style.display = 'none';
    document.getElementById('endgameSplash').style.display = '';
}

async function endgameSplash() {
    let title, icon, messagebody;
    if(json.retcode == 202) {
        title = "Gratulálunk!";
        icon = "success";
    }
    else {
        title = "Sajnáljuk!";
        icon = "error";
    }

    messagebody = copyGameTable();

    getEndGameStats().then(response => {
        messagebody += response;
        Swal.fire({
            title:  title,
            text:   json.uzenet,
            html:   messagebody,
            icon:   icon
        });
        //console.log(messagebody);
    });
}

async function sendMegoldas() {
    // A FormData objektum létrehozása
    const formData = new FormData();
    // Az action mező hozzáadása, hogy a WP tudja, melyik AJAX hívást kell kezelnie
    formData.append('action', 'szozat_megoldas');
    formData.append('security', SzozatAjax.nonce);

    for(let i = 0; i < betuszam; i++)
    {
        let elem = document.getElementById('jatekmezoinput_' + currentid + "_" + i);
        if (elem.value != "")
            formData.append("jatekmezoinput[]", elem.value);
    }
  
    try {
        const response = await fetch(SzozatAjax.ajax_url, {
            method: "POST",
            body: formData
        });
        eredmenyKiErtekel(await response.json());
        //console.log(await response.json());
    } catch (e) {
        console.error(e);
    }
}

async function getEndGameStats() {
    const formData = new FormData();
    // Az action mező hozzáadása, hogy a WP tudja, melyik AJAX hívást kell kezelnie
    formData.append('action', 'szozat_get_stats');
    formData.append('security', SzozatAjax.nonce);

    try {
        const response = await fetch(SzozatAjax.ajax_url, {
            method: "POST",
            body: formData
        });
        
        const html = await response.text();
        return html;
        //console.log(await response.json());
    } catch (e) {
        return e;
    }
}