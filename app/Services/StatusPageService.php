<?php

namespace App\Services;

use Ploi\Http\Response;

class StatusPageService extends PloiService
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @param $id
     * @return Response
     */
    public function getStatusPages($id = null): Response
    {
        return $this->ploi->statusPage($id)->get();
    }

    /**
     * @param $id
     * @return Response
     */
    public function getIncidents($id): Response
    {
        return $this->ploi->statusPage($id)->incident()->get();
    }

    /**
     * @param $id
     * @param $title
     * @param $description
     * @param $severity
     * @return Response
     */
    public function createIncident($id, $title, $description, $severity): Response
    {
        return $this->ploi->statusPage($id)->incident()->create($title, $description, $severity);
    }

    /**
     * @param $id
     * @return Response
     */
    public function deleteIncident($id): Response
    {
        return $this->ploi->statusPage()->incident($id)->delete();
    }

}
