<?php

namespace App\Services;

use Ploi\Http\Response;

class SiteService extends PloiService
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @return Response
     */
    public function list($serverId): Response
    {
        return $this->ploi->server($serverId)->sites()->get();
    }

    public function deploy($serverId, $siteId): Response
    {
        return $this->ploi->servers($serverId)->sites($siteId)->deployment()->deploy();
    }

    public function getSiteStatus($serverId, $siteId): \stdClass
    {
        return $this->ploi->servers($serverId)->sites($siteId)->get()->getData();
    }


}
