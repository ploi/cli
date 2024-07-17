<?php

namespace App\Services;

use Ploi\Http\Response;

class WebserverTemplateService extends PloiService
{

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @param null $id
     * @return Response
     */
    public function get($id = null): Response
    {
        return $this->ploi->webserverTemplates($id)->get();
    }
}
