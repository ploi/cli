<?php

namespace App\Services;

use App\Services\PloiService;
use Ploi\Http\Response;

class UserService extends PloiService
{

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @return Response
     */
    public function information(): Response
    {
        return $this->ploi->user()->get();
    }

    /**
     * @param $id
     * @return Response
     */
    public function serverProviders($id = null): Response
    {
        return $this->ploi->user()->serverProviders($id);
    }

}
