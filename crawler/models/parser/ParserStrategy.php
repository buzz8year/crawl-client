<?php

namespace crawler\models\parser;

use crawler\models\parser\Parser;
use crawler\models\parser\ParserFactory;
use crawler\models\parser\ParserSettler;
use Yii;

class ParserStrategy
{
    public ParserFactory $factory;
    public Parser $model;
    public string $url;


    public function __construct(ParserFactory $factory)
    {
        $this->factory = $factory;
    }

    public function setSessionClient(string $url)
    {
        $this->url = $url;

        switch ($this->factory->client['alias']) 
        {
            case 'zip': 
                return $this->zipSession();

            case 'file':
                return $this->fileSession();

            case 'curl':
                return $this->curlSession();

            case 'phantom': {
                $response = $this->phantomSession();
                if ($response) return $response;
                else throw new \Exception($this->factory->client['alias'] . ' Client session response is bad.');
            }
            // throw new \Exception('Ошибка подключения клиента');
            default: return null;
        }
    }

    /**
     * processFail() logs fails to history, shifts agent and proxy arrays and resets curl options
     *
     * @param string $type
     * @return void
     */
    public function handleResponse(int $status, string $client = '')
    {
        // $proxy = $this->factory->proxies[0] ?? [];
        // $agent = $this->factory->agents[0] ?? [];
        // $defClient = $client ? $client : $this->factory->getClientAlias();
        // ParserSettler::logSession($status, $this->url, $this->factory->model->id, $proxy, $agent, $defClient);

        switch ($status) 
        {
            case 0: break;
            case 1: array_shift($this->factory->proxies);
                break;
            case 2: array_shift($this->factory->agents);
                break;
        }

        $this->factory->setOptions();
    }

    /**
     * fileSession() function utilizes file_get_contents() function.
     *
     * @param string $url
     */
    public function fileSession()
    {
        if ($contents = file_get_contents($this->url)) {
            $this->handleResponse(0,  'file');
            return $contents;
        }
    }


    /**
     * zipSession()
     *
     * @param string $url
     */
    public function zipSession()
    {
        $zip = new \ZipArchive(); 
        file_put_contents('tmp.zip', file_get_contents($this->url));

        if ($zip->open('tmp.zip') === true) 
        {
            $data = $zip->getFromIndex(0);
            $zip->close();
            return $data;
        }
    }


    /**
     * curlSession() function utilizes $this->factory->options, and establishes a curl session based on them.
     * Conditionaly recursive.
     * Recursion condition is based on whether "proxy" used is successful or not.
     * If current proxy is failure, self:$proxies array is shifted, and $this->factory->options array is recreated.
     * If all proxies in array ($this->factory->proxies) are failure, then session goes finally proxy-less.
     * Proxy fails are written to history.
     *
     * @param string $url
     */
    public function curlSession()
    {
        if (!$curl = curl_init($this->url))
            throw new \Exception('Curl library problem.');

        if (!$copt = curl_setopt_array($curl, $this->factory->options))
            throw new \Exception('Setting Curl options was not successful.');

        $response = curl_exec($curl);
        $info = curl_getinfo($curl);
        curl_close($curl);


        if ((!$response || $info['http_code'] == 407) && $this->factory->proxies) 
        {
            $this->handleResponse(1);
            return $this->curlSession();
        }

        if ($info['http_code'] == 403 && $this->factory->agents) 
        {
            $this->handleResponse(2);
            return $this->curlSession();
        }

        if ($response) {
            // $captcha = $this->getNodes($response, '//*[contains(@*, \'captcha\')]');
            // if ($captcha && $captcha->length) {
            //     $this->processResponse(1, $url);
            //     $this->processResponse(2, $url);
            //     return $this->curlSession($url);
            // }
            $this->handleResponse(0);
            return $response;
        }
        else return null; 
        // throw new \Exception('HTTP Code ' . $info['http_code'] . '. Either url or Curl options are bad. URL: ' . $url);
    }




    /**
     * phantomSession() function utilizes $this->factory->options, and establishes a curl session based on them.
     *
     * @param string $url
     * @return array
     */
    public function phantomSession()
    {
        $phantom = Phantom::getInstance();
        // $phantom->getEngine()->debug(true);
        $phantom->getEngine()->setPath(Yii::getAlias('@phantom'));
        $phantom->getEngine()->addOption('--ignore-ssl-errors=true');
        $phantom->getEngine()->addOption('--load-images=false');

        $request  = $phantom->getMessageFactory()->createRequest($this->url);
        // $request->setDelay(0);
        // $request->setTimeout(0);

        $response = $phantom->getMessageFactory()->createResponse();

        $phantom->send($request, $response);

        $this->handleResponse(0, $this->url, 'phantom');
        
        // if (!$response->getContent() && $this->factory->proxies) {
        //     $this->processResponse(1, $url);
        //     return $this->phantomSession($url);
        // }

        return $response->getContent();
    }
}