<?php

namespace App\Traits;

trait HasRepo
{
    public function hasRepoInstalled($serverId, $siteId): bool
    {
        return $this->ploi->getRepository($serverId, $siteId)['data']['repository'] !== null;
    }
}
