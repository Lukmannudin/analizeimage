<?php
/**----------------------------------------------------------------------------------
* Microsoft Developer & Platform Evangelism
*
* Copyright (c) Microsoft Corporation. All rights reserved.
*
* THIS CODE AND INFORMATION ARE PROVIDED "AS IS" WITHOUT WARRANTY OF ANY KIND, 
* EITHER EXPRESSED OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE IMPLIED WARRANTIES 
* OF MERCHANTABILITY AND/OR FITNESS FOR A PARTICULAR PURPOSE.
*----------------------------------------------------------------------------------
* The example companies, organizations, products, domain names,
* e-mail addresses, logos, people, places, and events depicted
* herein are fictitious.  No association with any real company,
* organization, product, domain name, email address, logo, person,
* places, or events is intended or should be inferred.
*----------------------------------------------------------------------------------
**/

/** -------------------------------------------------------------
# Azure Storage Blob Sample - Demonstrate how to use the Blob Storage service. 
# Blob storage stores unstructured data such as text, binary data, documents or media files. 
# Blobs can be accessed from anywhere in the world via HTTP or HTTPS. 
#
# Documentation References: 
#  - Associated Article - https://docs.microsoft.com/en-us/azure/storage/blobs/storage-quickstart-blobs-php 
#  - What is a Storage Account - http://azure.microsoft.com/en-us/documentation/articles/storage-whatis-account/ 
#  - Getting Started with Blobs - https://azure.microsoft.com/en-us/documentation/articles/storage-php-how-to-use-blobs/
#  - Blob Service Concepts - http://msdn.microsoft.com/en-us/library/dd179376.aspx 
#  - Blob Service REST API - http://msdn.microsoft.com/en-us/library/dd135733.aspx 
#  - Blob Service PHP API - https://github.com/Azure/azure-storage-php
#  - Storage Emulator - http://azure.microsoft.com/en-us/documentation/articles/storage-use-emulator/ 
#
**/

require_once 'vendor/autoload.php';
require_once "./random_string.php";

use MicrosoftAzure\Storage\Blob\BlobRestProxy;
use MicrosoftAzure\Storage\Common\Exceptions\ServiceException;
use MicrosoftAzure\Storage\Blob\Models\ListBlobsOptions;
use MicrosoftAzure\Storage\Blob\Models\CreateContainerOptions;
use MicrosoftAzure\Storage\Blob\Models\PublicAccessType;

$connectionString = "DefaultEndpointsProtocol=https;AccountName=olegstore;AccountKey=yG2vxEgOCV/fIugr5BbMZdAVmQ4jpm77msKFG2Aa3XVBKkh8XwYpGP129lbjuyk9lBDJkZWZpFxEYUvMexQumA==;EndpointSuffix=core.windows.net";

// Create blob client.
$blobClient = BlobRestProxy::createBlobService($connectionString);

// $fileToUpload = "HelloWorld.txt";

if (!isset($_GET["Cleanup"])) {
    // Create container options object.
    $createContainerOptions = new CreateContainerOptions();

    // Set public access policy. Possible values are
    // PublicAccessType::CONTAINER_AND_BLOBS and PublicAccessType::BLOBS_ONLY.
    // CONTAINER_AND_BLOBS:
    // Specifies full public read access for container and blob data.
    // proxys can enumerate blobs within the container via anonymous
    // request, but cannot enumerate containers within the storage account.
    //
    // BLOBS_ONLY:
    // Specifies public read access for blobs. Blob data within this
    // container can be read via anonymous request, but container data is not
    // available. proxys cannot enumerate blobs within the container via
    // anonymous request.
    // If this value is not specified in the request, container data is
    // private to the account owner.
    $createContainerOptions->setPublicAccess(PublicAccessType::CONTAINER_AND_BLOBS);

    // Set container metadata.
    $createContainerOptions->addMetaData("key1", "value1");
    $createContainerOptions->addMetaData("key2", "value2");

    // $containerName = "blockblobs".generateRandomString();
    $containerName = "mycontainer";
    if(isset($_POST["submit"])) {
        $target_dir = "uploads/";
        $filename = str_replace(' ', '_', $_FILES["fileToUpload"]["name"]);
        $target_file = $target_dir . basename($filename);
        move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $target_file);
        $fileToUpload = "$target_dir"."$filename";
        $fileToUpload = str_replace(' ', '_', $fileToUpload);
        // $fileToUpload = "uploads/anjing.png";
        if (file_exists($target_file)) {
            try {
                // Create container.
                // $blobClient->createContainer($containerName, $createContainerOptions);

                // Getting local file so that we can upload it to Azure
                $myfile = fopen($fileToUpload, "r") or die("Unable to open file!");
                fclose($myfile);
                
                # Upload file as a block blob
                echo "Uploading BlockBlob: ".PHP_EOL;
                echo $fileToUpload;
                echo "<br />";
                
                $content = fopen($fileToUpload, "r");

                //Upload blob
                $blobClient->createBlockBlob($containerName, $fileToUpload, $content);
                
                // List blobs.
                $listBlobsOptions = new ListBlobsOptions();
                $listBlobsOptions->setPrefix("HelloWorld");

                echo "These are the blobs present in the container: ";

                do{
                    $result = $blobClient->listBlobs($containerName, $listBlobsOptions);
                    foreach ($result->getBlobs() as $blob)
                    {
                        echo $blob->getName().": ".$blob->getUrl()."<br />";
                    }
                
                    $listBlobsOptions->setContinuationToken($result->getContinuationToken());
                } while($result->getContinuationToken());
                echo "<br />";

                // Get blob.
                echo "This is the content of the blob uploaded: ";
                echo "<p id='targetFileBlob' style='display:none;'>".
                    "https://olegstore.blob.core.windows.net/".
                    $containerName."/".
                    $fileToUpload
                ."</p>";
                ?>
                <style>
                    button#processImage,.resultAnalize {
                        display:block !important;
                    }
                </style>
                <?php
                // $blob = $blobClient->getBlob($containerName, $fileToUpload);
                // fpassthru($blob->getContentStream());
                echo "<br />";
            }
            catch(ServiceException $e){
                // Handle exception based on error codes and messages.
                // Error codes and messages are here:
                // http://msdn.microsoft.com/library/azure/dd179439.aspx
                $code = $e->getCode();
                $error_message = $e->getMessage();
                echo $code.": ".$error_message."<br />";
            }
            catch(InvalidArgumentTypeException $e){
                // Handle exception based on error codes and messages.
                // Error codes and messages are here:
                // http://msdn.microsoft.com/library/azure/dd179439.aspx
                $code = $e->getCode();
                $error_message = $e->getMessage();
                echo $code.": ".$error_message."<br />";
            }
        }
    }
}
else 
{
    try{
        // Delete container.
        echo "Deleting Container".PHP_EOL;
        echo $_GET["containerName"].PHP_EOL;
        echo "<br />";
        $blobClient->deleteContainer($_GET["containerName"]);
    }
    catch(ServiceException $e){
        // Handle exception based on error codes and messages.
        // Error codes and messages are here:
        // http://msdn.microsoft.com/library/azure/dd179439.aspx
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
              // **********************************************
        // *** Update or verify the following values. ***
        // **********************************************
 
        // Replace <Subscription Key> with your valid subscription key.
        var sourceImageUrl = document.getElementById("targetFileBlob").textContent;
            
            document.querySelector("#sourceImage").src = sourceImageUrl;
        function processImage(){ 
            document.getElementById("responseTextArea").value = "On Proggress, Please Wait...";
        var subscriptionKey = "f756a031fd4b4335a3b3fe9842f86bed";
 
                // You must use the same Azure region in your REST API method as you used to
                // get your subscription keys. For example, if you got your subscription keys
                // from the West US region, replace "westcentralus" in the URL
                // below with "westus".
                //
                // Free trial subscription keys are generated in the "westus" region.
                // If you use a free trial subscription key, you shouldn't need to change
                // this region.
                var uriBase =
                    "https://australiaeast.api.cognitive.microsoft.com/vision/v2.0/analyze";
                    

                // Request parameters.
                var params = {
                    "visualFeatures": "Categories,Description,Color",
                    "details": "",
                    "language": "en",
                };

                // Display the image.
                // var sourceImageUrl = document.getElementById("inputImage").value;
                // var sourceImageUrl = "https://olegstore.blob.core.windows.net/mycontainer/people.jpg";
                // var sourceImageUrl = document.getElementById("targetFileBlob").textContent;
            
                // document.querySelector("#sourceImage").src = sourceImageUrl;
                
                // Make the REST API call.
                $.ajax({
                    url: uriBase + "?" + $.param(params),

                    // Request headers.
                    beforeSend: function(xhrObj){
                        xhrObj.setRequestHeader("Content-Type","application/json");
                        xhrObj.setRequestHeader(
                            "Ocp-Apim-Subscription-Key", subscriptionKey);
                    },

                    type: "POST",

                    // Request body.
                    data: '{"url": ' + '"' + sourceImageUrl + '"}',
                })

                .done(function(data) {
                    // Show formatted JSON on webpage.
                    $("#responseTextArea").val(JSON.stringify(data, null, 2));
                    // document.getElementById("captionJson").innerHTML = data.description.captions[0].text;
                    // document.getElementById("dataAnalize").innerHTML = data
                    console.log(data.description.captions[0].text);  
                })

                .fail(function(jqXHR, textStatus, errorThrown) {
                    // Display error message.
                    var errorString = (errorThrown === "") ? "Error. " :
                        errorThrown + " (" + jqXHR.status + "): ";
                    errorString += (jqXHR.responseText === "") ? "" :
                        jQuery.parseJSON(jqXHR.responseText).message;
                    alert(errorString);
                });
            }
        </script>