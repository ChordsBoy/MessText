<?php
require_once("/ServerSideFiles/vars.php");
$unfinishedIMGs = glob("$data_dir/unfinishedIMG/*.*");
foreach($unfinishedIMGs as $uimg){
    if(filemtime($uimg) < microtime(true) - 30){
        unlink($uimg);
    }
}
if($_SERVER["REQUEST_METHOD"] === "POST"){
    $ips = json_decode(file_get_contents("$data_dir/ips.json"), true);
    $ip = md5($_SERVER["HTTP_X_FORWARDED_FOR"]);
    $s = 15;
    if(isset($ips[$ip])){
        if($ips[$ip] + $s > time()){
            echo "You're sending images too quickly! Limit is currently 1 image every $s seconds.";
            exit;
        }
    }
    if(isset($_FILES["img"])){
        $tmp = $_FILES["img"]["tmp_name"];
        $filetype = mime_content_type($tmp);
        if($filetype === "image/png" || $filetype === "image/jpeg" || $filetype === "image/bmp" || $filetype === "image/webp"){
            $ext = explode("/", $filetype)[1];
            $id = str_shuffle("ABCDEFabcdef0123456789");
            $unfinished = "$data_dir/unfinishedIMG/$id.$ext";
            if(move_uploaded_file($tmp, $unfinished)){
                exec("ffmpeg -i $unfinished -vf \"scale=64:64:force_original_aspect_ratio=decrease\" -sws_flags neighbor $data_dir/IMG/$id.png > $data_dir/output.log 2>&1 < /dev/null &");
                $ips[$ip] = time() + $s;
                $ips = json_encode($ips);
                if($ips){
                    file_put_contents("$data_dir/ips.json", $ips);
                }
                echo "success";
            } else {
                echo "Error moving uploaded file. This is a problem with the server.";
            }
        } else {
            echo "An unsupported file type was uploaded.";
        }
    }
    exit;
} else if($_SERVER["REQUEST_METHOD"] === "GET"){
    if(isset($_GET["es"]) && $_GET["es"] === "true"){
        clearstatcache();
        $imgs = glob("$data_dir/IMG/*.png");
        if(sizeOf($imgs) > 0){
            $mtimes = 0;
            $mostRecentIMG = $imgs[0];
            foreach($imgs as $img){
                if(filemtime($img) >= $mtimes){
                    $mtimes = filemtime($img);
                    $mostRecentIMG = $img;
                }
            }
            header("Content-Type: text/event-stream");
            header("Cache-Control: no-cache");
            date_default_timezone_set("America/New_York");
            session_write_close();
            ob_implicit_flush(1);
            $i = 0;
            $time = 0;
            echo "data: \n\n";
            while(1){
                clearstatcache();
                $imgs = glob("$data_dir/IMG/*.png");
                if(sizeOf($imgs) > 0){
                    foreach($imgs as $img){
                        if(filemtime($img) > $mtimes){
                            $mtimes = filemtime($img);
                            $mostRecentIMG = $img;
                            echo "data:  " . microtime(true) . "\n\n";
                            break 1;
                        }
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
        }
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
            * {
                font-family:arial;
                margin:0px;
                padding:0px;
                color:white;
            }
            body {
                background-color:black;
                overflow-x:hidden;
            }
            html {
                height:100vh;
                margin:0px;
                padding:0px;
            }
            img, canvas {
                image-rendering:crisp-edges;
                image-rendering:pixelated;
                object-fit:contain;
            }
            #currentimg {
                box-shadow:0px 0px 10px 6px rgb(255,255,255);
                transition:0.3s box-shadow;
                position:relative;
                left:50%;
                top:20px;
                height:min(75vw, 200px);
                aspect-ratio:1;
                object-fit:contain;
                transform:translateX(-50%);
                animation-name:growShrink;
                animation-duration:0.7s;
                animation-direction:alternate;
                animation-timing-function:ease-in-out;
                animation-iteration-count:infinite;
                animation-play-state:running;
            }
            @keyframes growShrink {
                0% {transform:translateX(-50%) scale(1,1);}
                100% {transform:translateX(-50%) scale(0.98,0.98);}
            }
            #history {
                display:none;
                flex-flow:row wrap;
                justify-content:center;
                position:relative;
                top:0px;
                left:50%;
                width:50%;
                transform:translateX(-50%);
            }
            #history img {
                margin:64px;
                margin-top:96px;
                position:relative;
                transform:translateX(-50%) translateY(-50%) scale(1,1);
                transition:0.2s transform;
            }
            #history img:hover, #history img:active {
                transform:translateX(-50%) translateY(-50%) scale(2,2);
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
                left:50%;
                user-select:none;
                transform:translateX(-50%);
                outline:none;
            }
            button:active {
                background-color:rgb(100,100,100);
            }
        </style>
        <title>MessText.com™ - Now with Images!</title>
    </head>
    <body>
    <p id="announcement" style="user-select:none; font-size:5vmin; font-family:DS; letter-spacing:2px; width:100%; text-align:center; position:relative; top:0px; left:0px; margin-top:0vh; color:white; background-color:rgb(50,50,50); padding-top:1vh; padding-bottom:1vh;"><?php echo file_get_contents("announcement.txt");?></p>
    <h1 style="user-select:none; font-size:5vmin; width:100%; text-align:center; position:relative; top:0px; left:0px; margin-top:2vh;">The current <a href="/">MessText</a> Image is:</h1>
        <img id="currentimg" src="/currentimage.php?<?php echo microtime(true);?>">
        <br>
        <input id="file" style="display:none;" accept=".png, .jpg, .bmp, .webp" type="file"></input>
        <div>
            <canvas id="imgpreview" height="64" width="64" style="display:none; height:min(75vw, 200px); aspect-ratio:1; position:relative; top:50px; left:50%; transform:translateX(-50%);"></canvas>
            <img id="imgele" style="display:none;">
            <button id="pastesubmit" style="display:none; margin-top:50px;">Submit</button>
        </div>
        <br>
        <div style="position:relative; top:50px; left:50%; max-width:90%; transform:translateX(-50%); display:flex; flex-flow:column wrap; justify-content:space-between;">
<button id="actualsubmitlol" style="display:none; font-size:50px;">Submit</button><button id="cancel" style="margin-top:20px; width:60%; color:red; display:none; font-size:50px;">Cancel</button></div>
        <button id="submit" style="margin-top:50px;"><h2>Select or Paste an Image</h2><br>(will be downscaled to 64x64 pixels)</button>
        <br>
        <button id="showhistory" style="margin-top:50px;">Show History</button>
        <br>
        <div id="history">
        </div>
        <script>
            const file = document.querySelector("#file");
            const currentimg = document.querySelector("#currentimg");
            const submit = document.querySelector("#submit");
            const actualsubmitlol = document.querySelector("#actualsubmitlol");
            const cancel = document.querySelector("#cancel");
            window.addEventListener("load", function(){
                const showhistory = document.querySelector("#showhistory");
                const history = document.querySelector("#history");
                const imgpreview = document.querySelector("#imgpreview");
                const imgele = document.querySelector("#imgele"); 
                const canvpreview = imgpreview.getContext("2d");
                showhistory.onclick = function(){
                    if(history.style.display !== "flex"){
                        showhistory.textContent = "Hide History";
                        history.style.display = "flex";
                        fetch("/currentimage?history", {method: 'get'}).then(res => res.text()).then(res => {
                            if(res.length > 0){
                                history.querySelectorAll("*").forEach(r => {
                                    r.remove();
                                });
                                res.split("\n").forEach(id => {
                                    if(id.length > 0){
                                        const image = document.createElement("IMG");
                                        image.src = "/loadimage?id=" + id;
                                        image.style.display = "none";
                                        image.style.objectFit = "contain";
                                        history.appendChild(image);
                                        image.onload = function(){
                                            this.style.display = "inline-block";
                                        };
                                    }
                                });
                                history.style.display = "flex";
                            }
                        });
                    } else {
                        showhistory.textContent = "Show History";
                        history.style.display = "none";
                    }
                };
                setInterval(function(){
                    fetch("/?announce=true", {method: "get"}).then(res => res.text())
                    .then(res => {
                        if(res.length < 500){
                            document.getElementById("announcement").innerHTML = res;
                        }
                    })
                    .catch(err => {
                        console.log("Announcement failed to load");
                    });
            }, 60000);
            var blob = null;
                function pasteImage(event){
                    event.preventDefault();
                    var item = event.clipboardData.items;
                    canvpreview.clearRect(0,0,canvpreview.width,canvpreview.width);
                    if(item[0].type.indexOf("image") === 0){
                        blob = item[0].getAsFile();
                        imgele.src = URL.createObjectURL(blob);
                        imgele.onload = function(){
                            var r = imgele.width / imgele.height;
                            if(r < 0){
                                imgpreview.width = Math.round(64 * r); 
                            } else {
                                r = imgele.height/imgele.width;
                                imgpreview.height = Math.round(64 * r); 
                            }
                            canvpreview.drawImage(imgele, 0, 0, imgele.width, imgele.height, 0,0,imgpreview.width,imgpreview.height);
                            imgpreview.style.display = "";
                            file.value = null;
                            submit.style.display = "none";
                            cancel.style.display = "inline";
                            actualsubmitlol.style.display = "inline";
                        };  
                    } else if(item[1].type.indexOf("image") === 0){
                        blob = item[1].getAsFile();
                        imgele.src = URL.createObjectURL(blob);
                        imgele.onload = function(){
                            var r = imgele.width / imgele.height;
                            if(r < 0){
                                imgpreview.width = Math.round(64 * r); 
                            } else {
                                r = imgele.height/imgele.width;
                                imgpreview.height = Math.round(64 * r); 
                            }
                            canvpreview.drawImage(imgele, 0, 0, imgele.width, imgele.height, 0,0,imgpreview.width,imgpreview.height);
                            imgpreview.style.display = "";
                            file.value = null;
                            submit.style.display = "none";
                            cancel.style.display = "inline";
                            actualsubmitlol.style.display = "inline";
                        };
                    }
                }
                function Submit(){
                    const fd = new FormData();
                    if(blob !== null){
                        fd.append("img", blob);
                    } else {
                        if(file.files.length === 1){
                            fd.append("img", file.files[0]);
                        } else {
                            alert("Please select/paste an image first.");
                        }
                    }
                    if(blob !== null || file.files.length === 1){
                        fetch(location.pathname, {method:'post', body:fd}).then(res => res.text()).then(res => {
                            if(res === "success"){
                                file.value = null;
                                blob = null;
                                cancel.style.display = "none";
                                imgpreview.style.display = "none";
                                actualsubmitlol.style.display = "none";
                                submit.style.display = "block";
                            } else {
                                if(res.length > 0){
                                    alert(res);
                                }
                            }
                        });
                    }
                };
                file.oninput = function(){
                    if(this.files.length === 1){
                        imgele.src = URL.createObjectURL(this.files[0]);
                        imgele.onload = () => {
                            var r = imgele.width / imgele.height;
                            if(r < 0){
                                imgpreview.width = Math.round(64 * r); 
                            } else {
                                r = imgele.height/imgele.width;
                                imgpreview.height = Math.round(64 * r); 
                            }
                            canvpreview.drawImage(imgele, 0, 0, imgele.width, imgele.height, 0,0,imgpreview.width,imgpreview.height);
                            imgpreview.style.display = "";
                            submit.style.display = "none";
                            cancel.style.display = "inline";
                            actualsubmitlol.style.display = "inline";
                        };
                        blob = null;
                        submit.style.display = "none";
                        cancel.style.display = "inline";
                        actualsubmitlol.style.display = "inline";
                    } else {
                        cancel.style.display = "none";
                        actualsubmitlol.style.display = "none";
                        submit.style.display = "block";
                    }
                };
                submit.onclick = function(){
                    file.click();
                };
                actualsubmitlol.onclick = function(){
                    Submit();
                };
                cancel.onclick = function(){
                    actualsubmitlol.style.display = "none";
                    this.style.display = "none";
                    file.value = null;
                    blob = null;
                    imgpreview.style.display = "none";
                    submit.style.display = "block";
                };
                document.onpaste = function(event){
                    event.preventDefault();
                    pasteImage(event);
                };
                var es = new EventSource("?es=true");
                es.onopen = function(){
                    this.onmessage = function(event){
                        if(event.data.length > 0){
                            currentimg.src = "currentimage?" + parseFloat(event.data);
                        }
                    };
                };
            });
        </script>
    </body>
</html>
