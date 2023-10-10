<?php

function getFiles()
{
    $rdi = new \RecursiveDirectoryIterator('.', \RecursiveDirectoryIterator::SKIP_DOTS);
    $rii = new \RecursiveIteratorIterator($rdi);

    return $rii;

}//end getFiles()
