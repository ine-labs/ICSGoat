/*
Browser Update
Author: Graffino (http://www.graffino.com)
Version: 2.0.5
This is a modified version of the browser-update.org script
Copyright (c) 2007-2015, MIT Style License <browser-update.org/LICENSE.txt>
*/

/* jshint funcscope:true */

var $buo_show = function() {
    var op = this.op = window._buorgres;
    var jsv = 24;
    var tv = jsv;
    var ll = op.ll;
    var pageurl = op.pageurl || location.hostname || "x";
    var bb = $bu_getBrowser();

    function busprintf() {
        var args = arguments;
        var data = args[0];
        for (var k = 1; k < args.length; ++k) 
            data = data.replace(/%s/, args[k]);
        return data;
    }
    
    var t = {};
    
    t.en = '<b>Your web browser ({brow_name}) is out of date</b>. For more security, comfort and the best experience on this site: <a{up_but}>Update your browser</a> <a{ignore_but}>Ignore</a>';
    t.de = '<b>Ihr Browser ({brow_name}) ist veraltet</b>. Aktualisieren sie ihren Browser für mehr Sicherheit, Komfort und die einwandfreie Nutzung dieser Webseite. <a{up_but}>Browser aktualisieren</a> <a{ignore_but}>Ignorieren</a>';
    t.it = '<b>Il tuo browser ({brow_name}) non è aggiornato</b>. Ha delle falle di sicurezza e potrebbe non visualizzare correttamente le pagine di questo e altri siti. <a{up_but}>Actualice su navegador</a> <a{ignore_but}>Chiudi</a>';
    t.pl = 'Przeglądarka (%s), której używasz, jest przestarzała. Posiada ona udokumentowane <b>luki bezpieczeństwa, inne wady</b> oraz <b>ograniczoną funkcjonalność</b>. Tracisz możliwość skorzystania z pełni możliwości oferowanych przez niektóre strony internetowe. <a%s>Dowiedz się jak zaktualizować swoją przeglądarkę</a>.';
    t.es = '<b>Su navegador ({brow_name}) no está actualizado</b>. Tiene fallos de seguridad conocidos y podría no mostrar todas las características de este y otros sitios web. <a{up_but}>Averigüe cómo actualizar su navegador.</a> <a{ignore_but}>Cerrar</a>';
    t.nl = 'Uw browser (%s) is <b>oud</b>. Het heeft bekende <b>veiligheidsissues</b> en kan <b>niet alle mogelijkheden</b> weergeven van deze of andere websites. <a%s>Lees meer over hoe uw browser te upgraden</a>';
    t.pt = '<b>Seu navegador ({brow_name}) está desatualizado</b>. Ele possui falhas de segurança e pode apresentar problemas para exibir este e outros websites. <a{up_but}>Veja como atualizar o seu navegador</a> <a{ignore_but}>Fechar</a>';
    t.sl = 'Vaš brskalnik (%s) je <b>zastarel</b>. Ima več <b>varnostnih pomankljivosti</b> in morda <b>ne bo pravilno prikazal</b> te ali drugih strani. <a%s>Poglejte kako lahko posodobite svoj brskalnik</a>';
    t.ru = 'Ваш браузер (%s) <b>устарел</b>. Он имеет <b>уязвимости в безопасности</b> и может <b>не показывать все возможности</b> на этом и других сайтах. <a%s>Узнайте, как обновить Ваш браузер</a>';
    t.id = 'Browser Anda (%s) sudah <b>kedaluarsa</b>. Browser yang Anda pakai memiliki <b>kelemahan keamanan</b> dan mungkin <b>tidak dapat menampilkan semua fitur</b> dari situs Web ini dan lainnya. <a%s> Pelajari cara memperbarui browser Anda</a>';
    t.uk = 'Ваш браузер (%s) <b>застарів</b>. Він <b>уразливий</b> й може <b>не відображати всі можливості</b> на цьому й інших сайтах. <a%s>Дізнайтесь, як оновити Ваш браузер</a>';
    t.ko = '지금 사용하고 계신 브라우저(%s)는 <b>오래되었습니다.</b> 알려진 <b>보안 취약점</b>이 존재하며, 새로운 웹 사이트가 <b>깨져 보일 수도</b> 있습니다. <a%s>브라우저를 어떻게 업데이트하나요?</a>';
    t.rm = 'Tes navigatur (%s) è <b>antiquà</b>. El cuntegna <b>problems da segirezza</b> enconuschents e mussa eventualmain <b>betg tut las funcziuns</b> da questa ed autras websites. <a%s>Emprenda sco actualisar tes navigatur</a>.';
    t.jp = 'お使いのブラウザ「%s」は、<b>時代遅れ</b>のバージョンです。既知の<b>脆弱性</b>が存在するばかりか、<b>機能不足</b>によって、サイトが正常に表示できない可能性があります。 <a%s>ブラウザを更新する方法を確認する</a>';
    t.fr = '<b>Votre navigateur ({brow_name}) est périmé</b>. Il contient des failles de sécurité et pourrait ne pas afficher certaines fonctionnalités des sites internet récents. <a{up_but}>Mettre le navigateur à jour</a> <a{ignore_but}>Fermer</a>';
    t.da = 'Din browser (%s) er <b>for&aelig;ldet</b>. Den har kendte <b>sikkerhedshuller</b> og kan m&aring;ske <b>ikke vise alle funktioner</b> p&aring; dette og andre websteder. <a%s>Se hvordan du opdaterer din browser</a>';
    t.sq = 'Shfletuesi juaj (%s) është <b>ca i vjetër</b>. Ai ka <b>të meta sigurie</b> të njohura dhe mundet të <b>mos i shfaqë të gjitha karakteristikat</b> e kësaj dhe shumë faqeve web të tjera. <a%s>Mësoni se si të përditësoni shfletuesin tuaj</a>';
    t.ca = 'El teu navegador (%s) està <b>desactualitzat</b>. Té <b>vulnerabilitats</b> conegudes i pot <b>no mostrar totes les característiques</b> d\'aquest i altres llocs web. <a%s>Aprèn a actualitzar el navegador</a>';
    t.fa = 'مرورگر شما (%s) <b>از رده خارج شده</b> می باشد. این مرورگر دارای <b>مشکلات امنیتی شناخته شده</b> می باشد و <b>نمی تواند تمامی ویژگی های این</b> وب سایت و دیگر وب سایت ها را به خوبی نمایش دهد. <a%s>در خصوص گرفتن راهنمایی درخصوص نحوه ی به روز رسانی مرورگر خود اینجا کلیک کنید.</a>';
    t.sv = 'Din webbläsare (%s) är <b>föråldrad</b>. Den har kända <b>säkerhetshål</b> och <b>kan inte visa alla funktioner korrekt</b> på denna och på andra webbsidor. <a%s>Uppdatera din webbläsare idag</a>';
    t.hu = 'Az Ön böngészője (%s) <b>elavult</b>. Ismert <b>biztonsági hiányosságai</b> vannak és esetlegesen <b>nem tud minden funkciót megjeleníteni</b> ezen vagy más weboldalakon. <a%s>Itt talál bővebb információt a böngészőjének frissítésével kapcsolatban</a>		 ';
    t.gl = 'O seu navegador (%s) está <b>desactualizado</b>. Ten coñecidos <b>fallos de seguranza</b> e podería <b>non mostrar tódalas características</b> deste e outros sitios web. <a%s>Aprenda como pode actualizar o seu navegador</a>';
    t.cs = 'Váš prohlížeč (%s) je <b>zastaralý</b>. Jsou známy <b>bezpečnostní rizika</b> a možná <b>nedokáže zobrazit všechny prvky</b> této a dalších webových stránek. <a%s>Naučte se, jak aktualizovat svůj prohlížeč</a>';
    t.he = 'הדפדפן שלך (%s) <b>אינו מעודכן</b>. יש לו <b>בעיות אבטחה ידועות</b> ועשוי <b>לא להציג את כל התכונות</b> של אתר זה ואתרים אחרים. <a%s>למד כיצד לעדכן את הדפדפן שלך</a>';
    t.nb = 'Nettleseren din (%s) er <b>utdatert</b>. Den har kjente <b>sikkerhetshull</b> og <b>kan ikke vise alle funksjonene</b> på denne og andre websider. <a%s>Lær hvordan du kan oppdatere din nettleser</a>';
    t["zh-tw"] = '您的瀏覽器(%s) 需要更新。該瀏覽器有諸多安全漏洞，無法顯示本網站的所有功能。 <a%s>瞭解如何更新瀏覽器</a>';
    t.zh = '<b>您的网页浏览器 ({brow_name}) 已过期</b>。更新您的浏览器，以提高安全性和舒适性，并获得访问本网站的最佳体验。<a{up_but}>更新浏览器</a> <a{ignore_but}>忽略</a>';
    t.fi = 'Selaimesi (%s) on <b>vanhentunut</b>. Siinä on tunnettuja tietoturvaongelmia eikä se välttämättä tue kaikkia ominaisuuksia tällä tai muilla sivustoilla. <a%s>Lue lisää siitä kuinka päivität selaimesi</a>.';
    t.tr = 'Tarayıcınız (%s) <b>güncel değil</b>. Eski versiyon olduğu için <b>güvenlik açıkları</b> vardır ve görmek istediğiniz bu web sitesinin ve diğer web sitelerinin <b>tüm özelliklerini hatasız bir şekilde</b> gösteremeyecektir. <a%s>Tarayıcınızı nasıl güncelleyebileceğinizi öğrenin</a>';
    t.ro = 'Browser-ul (%s) tau este <b>invechit</b>. Detine <b>probleme de securitate</b> cunoscute si poate <b>sa nu afiseze corect</b> toate elementele acestui si altor site-uri. <a%s>Invata cum sa-ti actualizezi browserul.</a>';
    t.bg = 'Вашият браузър (%s) <b>не е актуален</b>. Известно е, че има <b>пропуски в сигурността</b> и може <b>да не покаже правилно</b> този или други сайтове. <a%s>Научете как да актуализирате браузъра си</a>.';
    t.el = 'Αυτός ο ιστότοπος σας υπενθυμίζει: Ο φυλλομετρητής σας (%s) είναι <b>παρωχημένος</b>. <a%s>Ενημερώστε το πρόγραμμα περιήγησής σας</a> για μεγαλύτερη ασφάλεια και άνεση σε αυτήν την ιστοσελίδα.';
    t.ar = 'متصفحك (%s) <b>منتهى الصلاحيه</b>. ويوجد به <b>ثغرات امنية</b> معروفة وقد <b>لا يُشغل كثير من الميزات</b> المتعلقه بهذه الموقع. <a%s>أضغط هنا</a>لتعرف كيف تقوم بتحديث متصفحك';
    t.sr = 'Vaš pretraživač (%s) je <b>zastareo</b>. Ima poznate <b>sigurnosne probleme</b> i najverovatnije <b>neće prikazati sve funkcionalnisti</b> ovog i drugih sajtova. <a%s>Nauči više o nadogradnji svog pretraživača</a>';
    t.la = 'Mēs vēlamies Jums atgādināt: Jūsu pārlūkprogramma (%s) ir novecojusi. <a>Atjauniniet savu pārlūkprogrammu</a>, lai uzlabotu drošību, ātrumu un pārlūkošanas ērtības šajā un citās lapās.';
    t.ga = 'Tá an líonléitheoir agat (%s) <b>as dáta</b>. Tá <b>laigeachtaí slándála</b> a bhfuil ar eolas ann agus b\'fhéidir <b>nach taispeánfaidh sé gach gné</b> den suíomh gréasáin seo ná cinn eile. <a%s>Foghlaim conas do líonléitheoir a nuashonrú</a>';
    t.lv = 'Jūsu pārlūkprogramma (%s) ir <b>novecojusi</b>.  Tai ir zināmas <b>drošības problēmas</b>, un tā var attēlot šo un citas  tīmekļa lapas <b>nekorekti</b>. <a%s>Uzzini, kā atjaunot savu pārlūkprogrammu</a>';
    t.no = 'Dette nettstedet ønsker å minne deg på: Din nettleser (%s) er <b>utdatert</b>. <a%s>Oppdater nettleseren din </a> for mer sikkerhet, komfort og den beste opplevelsen på denne siden.';
    t.th = 'เว็บไซต์นี้อยากจะเตือนคุณ: เบราว์เซอร์ (%s) ของคุณนั้น <b>ล้าสมัยแล้ว</b> <a%s>ปรับปรุงเบราว์เซอร์ของคุณ</a> เพื่อเพิ่ม ความปลอดภัย ความสะดวกสบายและประสบการณ์ที่ดีที่สุดในเว็บไซต์นี้';
    t.hi = 'यह वेबसाइट आपको याद दिलाना चाहती हैं: आपका ब्राउज़र (%s) <b> आउट ऑफ़ डेट </ b> हैं। <a%s> और अधिक सुरक्षा, आराम और इस साइट पर सबसे अच्छा अनुभव करने लिए आपके ब्राउज़र को अपडेट करें</a>।';
    t.sk = 'Chceli by sme Vám pripomenúť: Váš prehliadač (%s) je <b>zastaralý</b>. <a%s>Aktualizujte si ho</a> pre viac bezpečnosti, pohodlia a pre ten najlepší zážitok na tejto stránke.';
    t.vi = 'Website này xin nhắc bạn rằng: Trình duyệt (%s) của bạn hiện đã <b>lỗi thời</b>. <a%s>Hãy cập nhật trình duyệt của bạn</a> để tăng thêm tính bảo mật, sự tiện lợi và trải nghiệm tuyệt nhất trên trang web này.';
    
    t = op["text_" + ll] || op.text || t[ll] || t.en;
    var tar = "";

    if (op.newwindow) 
        tar = ' target="_blank" rel="noopener"';
    var div = this.op.div = document.createElement("div");
    div.id = "buorg";
    div.className = "buorg";
    
    var style = '<style>.buorg {background: #FDF2AB no-repeat 14px center url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAAAAXNSR0IArs4c6QAAAd9JREFUOBGNk0FrE1EUhb83GUfbNAmFSBR1U0tLi1hCkRoJhSJiaxB3wUUhxYW7lm6Kv6Brg101oBDoQtKVYO1fEKQ/oCBdFARBoQsbkEod73mYYMJI++DOnHfuuefex7xx/GetwpUI3ih9As/q8DVJGiaR4i7C63F4KLxv2F4V4f4V9BPaW/diHh6UJiaCG/l8ICwuSZtoYN1fjkI4uL7OWKOBsLhzGVine5ctJufmXDgzg0JYnHJJJj3cC/j0Hn4fbm/HxWLRh7A45XrEtuk5gnW4X4DbY+Wyi8plcrmcD2Fxyknzr0mPgZ3z1SREgysruCAgk8n4EBannDSJBub8+CqM3iyViGZncc6RzWZ9CItTThppOyZ+AiOcXZr6rb/dgzD0Bt0JzECcppBGWtXIxD9s83QEmo+mp6Ph3V1IpYjjmHa77Rul02lvyOkpRwsLfNjbOzmAWh3eOitOXYOjO5AZ39riUqXii2VQrVa9QavV8gY6ys+dHfYXF/U5fnyB4cBGWBqwm3t9aoqB+fmuUOJCoeBDuBPSeK3VqNatwbcnkB/Z2GCoVut21wQKrU5x533cbHKwvMw7+O427YLcNc0FLz3/45dJP0IcfoZN+12fD/VdqrOsjq3xITT+AJfefQgtzf4fAAAAAElFTkSuQmCC);}</style>';
    if (t.indexOf("{brow_name}") === -1) {
        t = busprintf(t, bb.t, ' id="buorgul" href="' + this.op.url + '"' + tar);
        style += "<style>.buorg {position:absolute;position:fixed;z-index:111111;    width:100%; top:0px; left:0px;    border-bottom:1px solid red;    text-align:left; cursor:pointer;    font: 12px Arial,sans-serif;color:#000;}    .buorg div { padding:5px 36px 5px 40px; }    .buorg>div>a,.buorg>div>a:visited{color:#E25600; text-decoration: underline;}    #buorgclose{position:absolute;right:6px;top:0px;height:20px;width:12px;font:12px bold;padding:0;}    #buorga{display:block;}    @media only screen and (max-width: 700px){.buorg div { padding:5px 15px 5px 35px; }}</style>";
        div.innerHTML = '<div>' + t + '<div id="buorgclose"><a id="buorga">&times;</a></div></div>' + style;
    } else {
        style += "<style>.buorg {background-position: 8px 8px; position:absolute;position:fixed;z-index:111111;    width:100%; top:0px; left:0px;    border-bottom:1px solid red;    text-align:left; cursor:pointer;  font: 12px Helvetica,Arial,sans-serif;    box-shadow: 0 0 5px rgba(0,0,0,0.2);}    .buorg div { padding: 5px 12px 5px 30px;  line-height: 1.7em; }    .buorg div a,.buorg div a:visited{   font-size: 12px; text-transform: uppercase;text-indent: 0; color: #fff;    text-decoration: none;    box-shadow: 0 0 2px rgba(0,0,0,0.4);    padding: 1px 10px;    border-radius: 2px;    font-weight: normal;    background: #5ab400;    white-space: nowrap;    margin: 0 2px; display: inline-block;}    #buorgig{ background-color: gray;}    @media only screen and (max-width: 700px){.buorg div { padding:5px 12px 5px 35px;line-height: 1.3em;}.buorg {background-position: 8px 8px;}.buorg div a {display:inline-block; margin:5px 2px 2px}}</style>";
        t = t.replace("{brow_name}", bb.t).replace("{up_but}", ' id="buorgul" href="' + this.op.url + '"' + tar).replace("{ignore_but}", ' id="buorgig" href=""');
        div.innerHTML = '<div>' + t + '</div>' + style;
    }
    this.op.text = t;
    document.body.insertBefore(div, document.body.firstChild);
    var me = this;
    div.onclick = function() {
        if (me.op.newwindow) 
            window.open(me.op.url, "_blank");
        else 
            window.location.href = me.op.url;
        me.op.setCookie(me.op.reminderClosed);
        me.op.onclick(me.op);
        return false;
    };
    try {
        document.getElementById("buorgul").onclick = function(e) {
            e = e || window.event;
            if (e.stopPropagation) 
                e.stopPropagation();
            else 
                e.cancelBubble = true;
            me.op.div.style.display = "none";
            hm.style.marginTop = me.op.bodymt;
            me.op.onclick(me.op);
            return true;
        };
    } catch (e) {}
    var hm = document.getElementsByTagName("html")[0] || document.body;
    this.op.bodymt = hm.style.marginTop;
    hm.style.marginTop = (div.clientHeight) + "px";
    (function(me) {
        (document.getElementById("buorga") || document.getElementById("buorgig")).onclick = function(e) {
            e = e || window.event;
            if (e.stopPropagation) 
                e.stopPropagation();
            else 
                e.cancelBubble = true;
            me.op.div.style.display = "none";
            hm.style.marginTop = me.op.bodymt;
            me.op.onclose(me.op);
            me.op.setCookie(me.op.reminderClosed);
            return false;
        };
    })(me);
    if (this.op.noclose) {
        var el = (document.getElementById("buorga") || document.getElementById("buorgig"));
        el.parentNode.removeChild(el);
    }
    this.op.onshow(this.op);
};

function $bu_getBrowser(ua_str) {
    var n,
        t,
        ua = ua_str || navigator.userAgent,
        donotnotify = false;
    var names = {
        i: 'Internet Explorer',
        e: "Edge",
        f: 'Firefox',
        o: 'Opera',
        s: 'Safari',
        n: 'Netscape',
        c: "Chrome",
        a: "Android Browser",
        y: "Yandex Browser",
        v: "Vivaldi",
        x: "Other"
    };
    function ignore(reason, pattern) {
        if (RegExp(pattern, "i").test(ua)) 
            return reason;
        }
    var ig = ignore("bot", "bot|spider|googlebot|facebook|slurp|bingbot|google web preview|mediapartnersadsbot|AOLBuild|Baiduspider|DuckDuckBot|Teoma") || ignore("discontinued browser", "camino|flot|k-meleon|fennec|galeon|chromeframe|coolnovo") || ignore("complicated device browser", "SMART-TV|SmartTV") || ignore("niche browser", "Dorado|SamsungBrowser|MIDP|wii|UCBrowser|Chromium|Puffin|Opera Mini|maxthon|maxton|dolfin|dolphin|seamonkey|opera mini|netfront|moblin|maemo|arora|kazehakase|epiphany|konqueror|rekonq|symbian|webos|PaleMoon|QupZilla|Otter|Midori|qutebrowser") || ignore("mobilew without upgrade path or landing page", "iphone|ipod|ipad|kindle|silk|blackberry|bb10|RIM|PlayBook|meego|nokia") || ignore("android(chrome) web view", "; wv");
    if (ig) 
        return {n: "x", v: 0, t: "other browser", donotnotify: ig};
    var mobile = (/iphone|ipod|ipad|android|mobile|phone|ios|iemobile/i.test(ua));
    var pats = [
        [
            "Trident.*rv:VV", "i"
        ],
        [
            "Trident.VV", "io"
        ],
        [
            "MSIE.VV", "i"
        ],
        [
            "Edge.VV", "e"
        ],
        [
            "Vivaldi.VV", "v"
        ],
        [
            "OPR.VV", "o"
        ],
        [
            "YaBrowser.VV", "y"
        ],
        [
            "Chrome.VV", "c"
        ],
        [
            "Firefox.VV", "f"
        ],
        [
            "Version.VV.{0,10}Safari", "s"
        ],
        [
            "Safari.VV", "so"
        ],
        [
            "Opera.*Version.VV", "o"
        ],
        [
            "Opera.VV", "o"
        ],
        ["Netscape.VV", "n"]
    ];
    for (var i = 0; i < pats.length; i++) 
        if (ua.match(new RegExp(pats[i][0].replace("VV", "(\\d+\\.?\\d?)")), "i")) {
            n = pats[i][1];
            break;
        }
    var v = parseFloat(RegExp.$1);
    if (!n) 
        return {n: "x", v: 0, t: names[n], mobile: mobile};
    if (ua.indexOf('Android') > -1) {
        var ver = parseInt((/WebKit\/([0-9]+)/i.exec(ua) || 0)[1], 10) || 2000;
        if (ver <= 534) 
            return {
                n: "a",
                v: ver,
                t: names.a,
                mob: true,
                donotnotify: donotnotify,
                mobile: mobile
            };
    }
    if (/windows.nt.5.0|windows.nt.4.0|windows.95|windows.98|os x 10.2|os x 10.3|os x 10.4|os x 10.5|os x 10.6|os x 10.7/.test(ua)) 
        donotnotify = "oldOS";
    if (n == "f" && (Math.round(v) == 45 || Math.round(v) == 52)) 
        donotnotify = "ESR";
    if (n == "so") {
        v = 4.0;
        n = "s";
    }
    if (n == "i" && v == 7 && window.XDomainRequest) 
        v = 8;
    if (n == "io") {
        n = "i";
        if (v > 6) 
            v = 11;
        else if (v > 5) 
            v = 10;
        else if (v > 4) 
            v = 9;
        else if (v > 3.1) 
            v = 8;
        else if (v > 3) 
            v = 7;
        else 
            v = 9;
        }
    if (n == "e") 
        return {
            n: "i",
            v: v,
            t: names[n] + " " + v,
            donotnotify: donotnotify,
            mobile: mobile
        };
    return {
        n: n,
        v: v,
        t: names[n] + " " + v,
        donotnotify: donotnotify,
        mobile: mobile
    };
}
var $buo = function(op, test) {
    var jsv = 24;
    var n = window.navigator,
        b;
    window._buorgres = this.op = op || {};
    var ll = op.l || (n.languages ? n.languages[0] : null) || n.language || n.browserLanguage || n.userLanguage || document.documentElement.getAttribute("lang") || "en";
    this.op.ll = ll = ll.replace("_", "-").toLowerCase().substr(0, 2);
    this.op.apiver = this.op.api || this.op.c || -1;
    var vsakt;
    if (typeof this.op.vsakt == 'undefined')  {
        vsakt = {
            i: 12,
            f: 50,
            o: 42,
            s: 10,
            n: 20,
            c: 55,
            y: 16.9,
            v: 1.6
        };
    } else {
        vsakt = this.op.vsakt;
    }
    var vsdefault = {
        i: 10,
        f: -4,
        o: -4,
        s: -2,
        n: 12,
        c: -4,
        a: 534,
        y: -1,
        v: -0.2
    };
    var vsmin;
    if (this.op.apiver < 4) 
        vsmin = {
            i: 9,
            f: 10,
            o: 20,
            s: 7,
            n: 12
        };
    else 
        vsmin = {
            i: 8,
            f: 5,
            o: 12.5,
            s: 6.2,
            n: 12
        };
    var myvs = op.vs || {};
    var vs = op.vs || vsdefault;
    for (b in vsdefault) {
        if (!vs[b]) 
            vs[b] = vsdefault[b];
        if (vsakt[b] && vs[b] >= vsakt[b]) 
            vs[b] = vsakt[b] - 0.2;
        if (vsakt[b] && vs[b] < 0) 
            vs[b] = vsakt[b] + vs[b];
        if (vsmin[b] && vs[b] < vsmin[b]) 
            vs[b] = vsmin[b];
        }
    this.op.vsf = vs;
    if (op.reminder < 0.1 || op.reminder === 0) 
        this.op.reminder = 0;
    else 
        this.op.reminder = op.reminder || 24;
    this.op.reminderClosed = op.reminderClosed || (24 * 7);
    this.op.onshow = op.onshow || function(o) {};
    this.op.onclick = op.onclick || function(o) {};
    this.op.onclose = op.onclose || function(o) {};
    var pageurl = this.op.pageurl = op.pageurl || location.hostname || "x";
    this.op.url = op.url || "//whatbrowser.org";
    this.op.newwindow = (op.newwindow !== false);
    this.op.test = test || op.test || (location.hash == "#test-bu") || (location.hash == "#test-bu-beta") || false;
    var bb = $bu_getBrowser();
    if (!this.op.test && (!bb || !bb.n || bb.n == "x" || bb.donotnotify !== false || (document.cookie.indexOf("browserupdateorg=pause") > -1 && this.op.reminder > 0) || bb.v > vs[bb.n] || (bb.mobile && op.mobile === false))) return;
    this.op.setCookie = function(hours) {
        document.cookie = 'browserupdateorg=pause; expires=' + new Date(new Date().getTime() + 3600000 * hours).toGMTString() + '; path=/';
    };
    if (this.op.reminder > 0) 
        this.op.setCookie(this.op.reminder);
    if (this.op.nomessage) {
        op.onshow(this.op);
        return;
    }
    $buo_show();
};
var $buoop = window.$buoop || {};
$buo($buoop);