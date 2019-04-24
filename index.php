<?php
require_once 'vendor/autoload.php';
require_once "./random_string.php";

use MicrosoftAzure\Storage\Blob\BlobRestProxy;
use MicrosoftAzure\Storage\Common\Exceptions\ServiceException;
use MicrosoftAzure\Storage\Blob\Models\ListBlobsOptions;
use MicrosoftAzure\Storage\Blob\Models\CreateContainerOptions;
use MicrosoftAzure\Storage\Blob\Models\PublicAccessType;

$connectionString = "DefaultEndpointsProtocol=https;AccountName=dicodinggolokstore;AccountKey=LLbTrj6RAWfJKQ5Hy7Q0pk93hNxzJMY8HAjW9XJwJn6PO/aC33fqhkY4TwugOmfwWtRaHuOFPc7/c2v5+2U5/A==;EndpointSuffix=core.windows.net";

$blobClient = BlobRestProxy::createBlobService($connectionString);

if (!isset($_GET["Cleanup"])) {
    $createContainerOptions = new CreateContainerOptions();
    $createContainerOptions->setPublicAccess(PublicAccessType::CONTAINER_AND_BLOBS);
    $createContainerOptions->addMetaData("key1", "value1");
    $createContainerOptions->addMetaData("key2", "value2");
    $containerName = "mycontainer";
    if(isset($_POST["submit"])) {
        $target_dir = "uploads/";
        $filename = str_replace(' ', '_', $_FILES["fileToUpload"]["name"]);
        $target_file = $target_dir . basename($filename);
        move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $target_file);
        $fileToUpload = "$target_dir"."$filename";
        $fileToUpload = str_replace(' ', '_', $fileToUpload);
        if (file_exists($target_file)) {
            try {
                $myfile = fopen($fileToUpload, "r") or die("Unable to open file!");
                fclose($myfile);

                echo "Uploading BlockBlob: ".PHP_EOL;
                echo $fileToUpload;
                echo "<br />";
                
                $content = fopen($fileToUpload, "r");

                $blobClient->createBlockBlob($containerName, $fileToUpload, $content);
                
                $listBlobsOptions = new ListBlobsOptions();
                $listBlobsOptions->setPrefix("HelloWorld");

                echo "These are the blobs present in the container: ";

                do {
                    $result = $blobClient->listBlobs($containerName, $listBlobsOptions);
                    foreach ($result->getBlobs() as $blob) {
                        echo $blob->getName().": ".$blob->getUrl()."<br />";
                    }
                
                    $listBlobsOptions->setContinuationToken($result->getContinuationToken());
                } while($result->getContinuationToken());
                echo "<br />";
                echo "This is the content of the blob uploaded: ";
                echo "<p id='targetFileBlob' style='display:none;'>".
                    "https://dicodinggolok.blob.core.windows.net/". $containerName. "/". $fileToUpload .
                    "</p>";
                ?>
                <style>
                    button#processImage,.resultAnalize {
                        display:block !important;
                    }
                </style>
                <?php
                echo "<br />";
            } catch(ServiceException $e) {
                $code = $e->getCode();
                $error_message = $e->getMessage();
                echo $code.": ".$error_message."<br />";
            } catch(InvalidArgumentTypeException $e) {
                $code = $e->getCode();
                $error_message = $e->getMessage();
                echo $code.": ".$error_message."<br />";
            }
        }
    }
} else {
    try {
        echo "Deleting Container".PHP_EOL;
        echo $_GET["containerName"].PHP_EOL;
        echo "<br />";
        $blobClient->deleteContainer($_GET["containerName"]);
    }
    catch(ServiceException $e) {
        $code = $e->getCode();
        $error_message = $e->getMessage();
        echo $code.": ".$error_message."<br />";
    }
}
?>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.9.0/jquery.min.js"></script>
<style>
    .main {
        display:flex;
    }

    .form{
        width:50%;
        height:500px;
    }

    .form .inner-form {
        width:300px;
        height:100px;
        margin: 20% auto;
        padding:1%;
        border: black 2px dotted;
    }

    .body {
        width:50%;
        height:500px;
    }

    .button {
        background-color: #4CAF50; /* Green */
        border: none;
        color: white;
        padding: 15px 32px;
        text-align: center;
        text-decoration: none;
        display: inline-block;
        font-size: 16px;
        margin: 4px 2px;
        cursor: pointer;
    }
</style>    
    <div class="main">
        <div class="form">
            <div class="inner-form">
                <form action="index.php" method="post" enctype="multipart/form-data">
                    Select image to analize:
                    <input type="file" name="fileToUpload" id="fileToUpload">
                    <br><br>
                    <input type="submit" value="Upload" name="submit">
                </form>
            </div>
            <div class="inner-form" style="border:none;">
                <button class="button" style="display:none;" id="processImage" onclick="processImage()">Analize Image</button>
            </div>
        </div>
        <div class="body">
            <center>
                <img id="sourceImage" width="400" style="margin-top:60px;"/>
            </center>   
            <div class="resultAnalize" style="display:none;">
                <h3>Hasil Analisis</h3>
                <textarea id="responseTextArea" style="width:580px; height:400px;"></textarea> 
            </div>
        </div>
    </div>
    <script>
        var sourceImageUrl = document.getElementById("targetFileBlob").textContent;
            
        document.querySelector("#sourceImage").src = sourceImageUrl;
        function processImage(){ 
            document.getElementById("responseTextArea").value = "On Proggress, Please Wait...";
            var subscriptionKey = "e36088d6afc04c74abf2c3b4bfb14276";
    
            var uriBase = "https://australiaeast.api.cognitive.microsoft.com/vision/v2.0/analyze";     

            var params = {
                "visualFeatures": "Categories,Description,Color",
                "details": "",
                "language": "en",
            };

            $.ajax({
                url: uriBase + "?" + $.param(params),
                beforeSend: function(xhrObj){
                    xhrObj.setRequestHeader("Content-Type","application/json");
                    xhrObj.setRequestHeader("Ocp-Apim-Subscription-Key", subscriptionKey);
                },
                type: "POST",
                data: '{"url": ' + '"' + sourceImageUrl + '"}',
            }).done(function(data) {
                $("#responseTextArea").val(JSON.stringify(data, null, 2));
                console.log(data.description.captions[0].text);  
            }).fail(function(jqXHR, textStatus, errorThrown) {
                var errorString = (errorThrown === "") ? "Error. " : errorThrown + " (" + jqXHR.status + "): ";
                errorString += (jqXHR.responseText === "") ? "" : jQuery.parseJSON(jqXHR.responseText).message;
                alert(errorString);
            });
        }
    </script>