<?php

namespace App\Services;

use Ploi\Exceptions\Resource\RequiresId;
use Ploi\Http\Response;

class ScriptService extends PloiService
{

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @param $id
     * @return Response
     */
    public function getScripts($id = null): Response
    {
        return $this->ploi->scripts($id)->get();
    }

    /**
     * @param $label
     * @param $user
     * @param $content
     * @return Response
     */
    public function createScript($label, $user, $content): Response
    {
        return $this->ploi->scripts()->create($label, $user, $content);
    }

    /**
     * @param $id
     * @return Response
     */
    public function deleteScript($id): Response
    {
        return $this->ploi->scripts($id)->delete();
    }

    /**
     * @throws RequiresId
     */
    public function runScript(int $id = null, array $serverIds = []): Response
    {
        return $this->ploi->scripts()->run($id, $serverIds);
    }


}
