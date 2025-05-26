var currentid = cursor = 0;
var lastkey = null;
var jatekaktiv = true;
const betuszam = document.getElementById('jatektabla').getAttribute('data-betuszam');
const EgykarakteresBetukEsSzamok = [
    "A", "Á", "B", "C", "D", "E", "É", "F", "G", "H",
    "I", "Í", "J", "K", "L", "M", "N", "O", "Ó", "Ö", "Ő",
    "P", "Q", "R", "S", "T", "U", "Ú", "Ü", "Ű", "V", "W", "X", "Y", "Z",
    "0", "1", "2", "3", "4", "5", "6", "7", "8", "9"
];

// 0. elem = nincs találat, piros, 1. elem = találat rossz helyen, sárga, 2. elem találat jó helyen, zöld
const colors = [
    'var(--offline)',
    'var(--important)',
    'var(--online)'
]

window.onload = function() {
    setRow();
    jumpToKey(0);
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

function jumpToNext(id ) {
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

function eredmenyKiErtekel(rawjson) {
    let json = rawjson.data;
    if(json.retcode < 200 || json.retcode > 204)
    {
        if(json.retcode == 410)
            disableAllFields();

        alert(json.uzenet);
        if(json.retcode == 406)
            setRow();
    }
    else
    {
        for(let i = 0; i < betuszam; i++)
        {
            let bevitelem = document.getElementById('jatekmezoinput_' + currentid + "_" + i);
            let billentyu = document.getElementById('bill-' + bevitelem.value.toLowerCase());
            bevitelem.disabled = true;

            if(json.eredmeny[i] > 0)
                document.getElementById('jatekmezo_' + currentid + "_" + i).style.backgroundColor = colors[json.eredmeny[i]];

            // Csak akkor változtatunk színt, ha még nincs háttérszín a billentyűn, vagy zöldre állítjuk, mivel csak a sárga->zöld átmenet legális
            if(!billentyu.style.backgroundColor || json.eredmeny[i] == 2)
                billentyu.style.backgroundColor = colors[json.eredmeny[i]]
        }

        if(json.retcode == 202 || json.retcode == 204)
        {
            // A késleltetés nélkül korábban jelenik meg az üzenet, mint ahogy a háttérben a script befejeződik
            setTimeout(() => {
                alert(json.uzenet);
                disableAllFields();
            }, 0);
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
