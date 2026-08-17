<?php


namespace MonoPay;


class RequestBuilder
{
    protected function getDataFromGuzzleResponse(\Psr\Http\Message\ResponseInterface $response): array
    {
        $json = $response->getBody()->getContents();
        if(!$json && $response->getStatusCode() == 200){
            return [];
        }
        if(!$json){
            throw new \Exception('Empty request from Mono API',500);
        }
        $data = json_decode($json,true);
        if(!$data){
            throw new \Exception('Cannot decode json response from Mono (HTTP '.$response->getStatusCode().'): '.$json, 500);
        }
        if ($response->getStatusCode() == '200') {
            return $data;
        } elseif(isset($data['errorDescription'])){
            throw new \Exception($data['errorDescription'], $response->getStatusCode());
        } elseif (isset($data['errCode']) && isset($data['errText'])){
            throw new \Exception('Error: '.$data['errText'].'. Error code: '.$data['errCode'], $response->getStatusCode());
        }else{
            throw new \Exception('Unknown error response: '.$json, $response->getStatusCode());
        }
    }

}