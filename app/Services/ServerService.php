<?php

namespace App\Services;

use Ploi\Http\Response;

class ServerService extends PloiService
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @return Response
     */
    public function list(): Response
    {
        return $this->ploi->servers()->get();
    }

    /**
     * @param $id
     * @return Response
     */
    public function get($id): Response
    {
        return $this->ploi->projects($id)->get();
    }

    /**
     * @param string $title
     * @param array $servers
     * @param array $sites
     * @return Response
     */
    public function create(string $title, array $servers = [], array $sites = []): Response
    {
        return $this->ploi->projects()->create($title, $servers, $sites);
    }

    /**
     * @param string $id
     * @param string $title
     * @param array $servers
     * @param array $sites
     * @return Response
     */
    public function update(string $id, string $title, array $servers = [], array $sites = []): Response
    {
        return $this->ploi->projects($id)->update($title, $servers, $sites);
    }

    /**
     * @param string $id
     * @return Response
     */
    public function delete(string $id): Response
    {
        return $this->ploi->projects($id)->delete();
    }


}
