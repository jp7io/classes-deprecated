<?php

interface Jp7_Interadmin_Upload_AdapterInterface
{
    public function imageUrl($path, $template);

    public function url($path);

    public function hasPurging();

    public function purge($path);
}
