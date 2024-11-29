<?php
require_once("/ServerSideFiles/MessTextData/vars.php");
if($_SERVER["REQUEST_METHOD"] === "POST"){
    $ips = json_decode(file_get_contents("$data_dir/ips.json"), true);
    if(isset($_SERVER["HTTP_X_FORWARDED_FOR"])){
        $ip = md5($_SERVER["HTTP_X_FORWARDED_FOR"]);
    } else {
        $ip = md5("000.000.000.000");
    }
    $s = 3;
    if(isset($ips[$ip])){
        if($ips[$ip] + $s > time()){
            echo "You're sending messages too quickly! Limit is currently 1 message every $s seconds.";
            exit;
        } else {
            unset($ips[$ip]);
            file_put_contents("$data_dir/ips.json", json_encode($ips));
        }
    }
    if(isset($_POST["newString"])){
        if(file_get_contents("currenttext.txt") !== strval($_POST["newString"])){
            clearstatcache();
            file_put_contents("history.txt", filemtime("currenttext.txt") . ":" . file_get_contents("currenttext.txt") . PHP_EOL, FILE_APPEND);
            file_put_contents("currenttext.txt", str_replace(PHP_EOL, "", preg_replace('/(\pM{2})\pM+/u', '\1', substr($_POST["newString"], 0, 2000))));
            file_put_contents("total.txt", intval(file_get_contents("total.txt")) + 1);
            $ips[$ip] = time() + $s;
            $ips = json_encode($ips);
            if($ips){
                file_put_contents("$data_dir/ips.json", $ips);
            }
        }
    }
    exit;
} else if($_SERVER["REQUEST_METHOD"] === "GET"){
    if(isset($_GET["es"]) && $_GET["es"] === "true" && $_SERVER["HTTP_ACCEPT"] === "text/event-stream"){
        $text = file_get_contents("currenttext.txt");
        header("Content-Type: text/event-stream");
        header("Cache-Control: no-cache");
        ignore_user_abort(1);
        date_default_timezone_set("America/New_York");
        session_write_close();
        ob_implicit_flush(1);
        $i = 0;
        $time = 0;
        while(connection_aborted() === 0){
            clearstatcache();
            if(filemtime("currenttext.txt") > $time){
                echo "data: " . json_encode(array(0 => filemtime("currenttext.txt"), 1 => file_get_contents("currenttext.txt"), "total" => sizeOf(explode(PHP_EOL, file_get_contents("history.txt"))))) . "\n\n";
                $time = filemtime("currenttext.txt");
            } else {
                if($i === 450){
                    echo "data: \n\n";
                }
            }
            if($i === 450){
                $i = 0;
            }
            while(ob_get_level() > 0){
                ob_end_flush();
            }
            $i++;
            usleep(100000);
        }
        exit;
    } else if(isset($_GET["history"]) && $_GET["history"] === "true"){
        $history = explode(PHP_EOL, file_get_contents("history.txt"));
        echo json_encode(array_slice($history, sizeOf($history) - 251));
        exit;
    } else if(isset($_GET["announce"]) && $_GET["announce"] === "true"){
        echo file_get_contents("announcement.txt");
        exit;
    }
}
?>
<!DOCTYPE html>
<html>
    <head>
        <meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=0">
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />
        <meta name="title" content="MessText.com - Mess with the Text">
        <meta property="og:title" content="MessText.com - Mess with the Text">
        <meta name="description" content="A publicly-editable message for everyone to mess with!">
        <meta property="og:description" content="A publicly-editable message for everyone to mess with!">
        <meta property="og:url" content="https://messtext.com">
        <meta property="og:site_name" content="MessText">
        <meta property="og:keywords" content="web,fun,text,message,social,public,cool,funny,entertainment,boredom,bored button,gimmicky">
        <style>
            @font-face {
                font-family:DS;
                src:url('Fonts/DS.ttf');
            }
            body {
                background-color:black;
                overflow-x:hidden;
                background-image: linear-gradient(to bottom, #00000088 0%, #00000088 100%), url("gif0.gif"), url("gif1.gif"), url("gif2.gif"), url("gif3.gif"), url("background.jpg");
                background-size:cover, 10%, 20%, 15%, 16%, cover;
                background-attachment:fixed;
                background-position:center center, 48% 2%, 14% 32%, 74% 14%, bottom right, center;
                background-repeat:no-repeat;
            }
            html {
                height:100vh;
                margin:0px;
                padding:0px;
            }
            .main {
                font-family:DS;
                resize:none;
                outline:none;
                text-align:center;
                border:none;
                overflow-x:hidden;
                overflow-y:hidden;
                white-space:pre-wrap;
                background-color:transparent;
                color:white;
                position:relative;
                width:90%;
                left:5%;
                margin:0px;
                margin-top:10vh;
                padding:0px;
                animation-name:growShrink;
                animation-duration:0.7s;
                animation-direction:alternate;
                animation-timing-function:ease-in-out;
                animation-iteration-count:infinite;
                animation-play-state:running;
            }
            .main:focus {
                outline:0.1vmin solid white;
                animation-name:no-animate;
                transform:scale(1,1);
            }
            * {
                color:white;
                font-family:arial;
                margin:0px;
                padding:0px;
            }
            @keyframes growShrink {
                0% {transform:scale(1,1);}
                100% {transform:scale(0.98,0.98);}
            }
            button {
                background-color:black;
                border:0.4vmin solid rgb(200,200,200);
                border-radius:1vmin;
                transition: 0.2s background-color;
                min-height:4vh;
                padding:1vh;
                position:relative;
                font-size:5vh;
                cursor:pointer;
                font-family:DS;
            }
            button:active {
                background-color:rgb(100,100,100);
            }
            #history {
                background-color:rgba(0,0,0,0.7);
                font-family:DS;
                width:90%;
                position:relative;
                left:5%;
                transition:0.2s height;
            }
            .historyMessage {
                font-family:DS;
                width:100%;
                font-size:5vh;
                position:relative;
            }
        </style>
        <title>MessText.com™</title>
    </head>
<body>
    <p id="announcement" style="user-select:none; font-size:5vmin; font-family:DS; letter-spacing:2px; width:100%; text-align:center; position:relative; top:0px; left:0px; margin-top:0vh; color:white; background-color:rgb(50,50,50); padding-top:1vh; padding-bottom:1vh;"><?php echo file_get_contents("announcement.txt");?></p>
    <h1 style="z-index:-1; user-select:none; font-size:5vmin; width:100%; text-align:center; position:relative; top:0px; left:0px; margin-top:2vh;">The current MessText is:</h1>
    <textarea class="main" maxlength="2000" placeholder="Edit this text" style="font-size:10vmin; height:0px;" value="<?php echo str_replace("\"", htmlspecialchars("\"", ENT_QUOTES), file_get_contents("currenttext.txt"));?>"></textarea>
    <p id="modif" style="font-family:arial; user-select:none; opacity:0.6; font-size:3vmin; width:100%; text-align:center; margin:0px; padding:0px; position:relative; top:6vh;">Last Change: </p>
    <h1 style="user-select:none; font-size:3vmin; width:100%; text-align:center; position:relative; top:0px; left:0px; margin-top:6vh;">Changes to MessText are public. Enjoy xd</h1>
    <p id="modif" style="font-family:arial; user-select:none; opacity:1; font-size:3vmin; width:100%; text-align:center; margin:0px; padding:0px; position:relative; top:4vh;"><label style="opacity:0.7; text-decoration:none;"> Created by </label><b><a href="https://cosmoa.net/galaxy/ChordsBoy/" style="color:rgb(0,255,0);">ChordsBoy</a></b><br><i style="user-select:text;">Bitcoin donations: 3Da2gHwYMLRDCj1NVwJGi3HBTfzzaKPap6</i><br><b><a href="https://ko-fi.com/chordsboy" style="color:white;">Ko-fi donations</a></b></p>
        <button id="historyButton" style="left:50%; transform:translateX(-50%); margin-top:8vh;">Show History</button>
    <script>
        window.onload = function(){
            const main = document.querySelector(".main");
            const modif = document.querySelector("#modif");
            const hb = document.querySelector("#historyButton");
            var s = 1;
            var counter = <?php echo sizeOf(explode(PHP_EOL, file_get_contents("history.txt")));?>;
            var currentMessage = main.value;
            var initialMessage = main.value;
            var dateModified = new Date(<?php echo filemtime("currenttext.txt");?> * 1000);
            modif.innerText = `Last Change:` + dateModified.toLocaleDateString() + " " + dateModified.toLocaleTimeString() + `
Total number of changes: ` + counter;
            function updateText(){
                const es = new EventSource("?es=true");
                es.onopen = function(){
                    this.onmessage = function(event){
                        if(event.data.length > 0){
                            if(document.activeElement !== main){
                                main.style.height = "0px";
                                main.value = JSON.parse(event.data)[1];
                                main.style.height = main.scrollHeight + "px";
                            }
                            counter = JSON.parse(event.data).total;
                            currentMessage = JSON.parse(event.data)[1];
                            dateModified = new Date(JSON.parse(event.data)[0] * 1000);
                            modif.innerText = `Last Change:` + dateModified.toLocaleDateString() + " " + dateModified.toLocaleTimeString() + `
Total number of changes: ` + counter;
                        }
                    };
                };
            }
            setInterval(function(){
                fetch(location.pathname + "?announce=true", {method: "get"}).then(res => res.text())
                .then(res => {
                    if(res.length < 500){
                        document.getElementById("announcement").innerHTML = res;
                    }
                })
                .catch(err => {
                    console.log("Announcement failed to load");
                });
            }, 60000);
            updateText();
            hb.onclick = function(){
                if(!document.getElementById("history")){
                    const h = document.createElement("DIV");
                    h.id = "history";
                    h.style.height = "0px";
                    document.body.appendChild(h);
                    fetch(location.pathname + "?history=true", {method: "get"}).then(res => res.text()).then(res => {
                        var history = JSON.parse(res).reverse();
                        for(var i = 1; i < history.length; i++){
                            const hm = document.createElement("DIV");
                            hm.className = "historyMessage";
                            hm.style.fontFamily = "DS";
                            const ts = new Date(parseInt(history[i].split(":")[0]) * 1000);
                            hm.innerHTML = "<label style='font-family:DS; opacity:0.5;'>" + ts.toLocaleDateString() + " " + ts.toLocaleTimeString() + "</label><div class='hm' style='font-family:DS; border-bottom:0.2vmin solid white;'></div>";
                            h.appendChild(hm);
                            hm.querySelector(".hm").textContent = history[i].slice(history[i].indexOf(":") + 1);
                        }
                    });
                    h.style.height = h.scrollHeight + "px";
                    hb.innerHTML = "Hide History";
                } else {
                    document.getElementById("history").remove();
                    hb.innerHTML = "Show History";
                }
                main.style.height = main.scrollHeight + "px";
            };
            main.onblur = function(event){
                if(main.value !== initialMessage){
                    const fd = new FormData();
                    fd.append("newString", main.value);
                    fetch(location.pathname, {method: "post", body: fd}).then(res => res.text()).then(res => {
                        if(res.length > 0){
                            alert(res);
                        }
                    });
                    initialMessage = main.value;
                } else {
                    initialMessage = currentMessage;
                    main.value = currentMessage;
                }
                this.style.height = "0px";
                this.style.height = this.scrollHeight + "px";
            };
            document.onkeydown = function(event){
                if(event.keyCode === 13){
                    main.blur();
                }
            };
            window.onresize = function(){
                main.style.height = "0px";
                main.style.height = main.scrollHeight + "px";
            };
            main.oninput = function(){
                this.value = this.value.replace(/\n/, "");
                this.style.height = "0px";
                this.style.height = this.scrollHeight + "px";
            };
            main.style.height = main.scrollHeight + "px";
        };
    </script>
</body>
</html>
