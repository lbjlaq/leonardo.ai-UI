<?php
require_once('vendor/autoload.php');

$client = new \GuzzleHttp\Client(['verify' => false]);

function respondWithError($message) {
    http_response_code(500);
    echo json_encode(["error" => $message]);
    exit();
}

try {
    // Required prompt
    if(!isset($_POST['prompt'])) {
        respondWithError('Prompt is required');
    }

    $data = ['prompt' => $_POST['prompt']];  // prompt is mandatory

    // Optional fields
    if (!empty($_POST['negative_prompt'])) $data['negative_prompt'] = $_POST['negative_prompt'];
    if (!empty($_POST['modelId'])) $data['modelId'] = $_POST['modelId'];
    if (!empty($_POST['sd_version'])) $data['sd_version'] = $_POST['sd_version'];
    if (!empty($_POST['presetStyle'])) $data['presetStyle'] = $_POST['presetStyle'];


    // Default num_images to 1 if not provided
    $data['num_images'] = empty($_POST['num_images']) ? 1 : (int)$_POST['num_images'];

    $response = $client->request('POST', 'https://cloud.leonardo.ai/api/rest/v1/generations', [
        'headers' => [
            'accept' => 'application/json',
            'authorization' => 'Bearer yourBearerkey',
            'content-type' => 'application/json',
        ],
        'json' => $data,
    ]);

    $generationId = json_decode($response->getBody()->getContents())->sdGenerationJob->generationId;

    // Wait for the generation process to complete...
    sleep(30); // Wait time may need adjustments according to the API's processing time

    $response = $client->request('GET', "https://cloud.leonardo.ai/api/rest/v1/generations/$generationId", [
        'headers' => [
            'accept' => 'application/json',
            'authorization' => 'Bearer yourBearerkey',
        ],
    ]);

    $generated_images = json_decode($response->getBody()->getContents())->generations_by_pk->generated_images;
    $image_urls = array_map(function($i) { return $i->url; }, $generated_images);

    echo json_encode([
        'generationId' => $generationId,
        'images' => $image_urls
    ]);
} catch (GuzzleHttp\Exception\ClientException $e) {
    respondWithError('Client error: ' . $e->getMessage());
} catch (GuzzleHttp\Exception\ServerException $e) {
    respondWithError('Server error: ' . $e->getMessage());
} catch (Exception $e) {
    respondWithError('Unexpected error: ' . $e->getMessage());
}
?>
