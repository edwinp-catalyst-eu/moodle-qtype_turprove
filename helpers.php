<?php

defined('MOODLE_INTERNAL') || die();

function tur_getcustomfraction($numAnswers) {
    $turfraction = 0;
    switch ($numAnswers) {
        case 1:
            $turfraction = 1;
            break;
        case 2:
            $turfraction = 0.5;
            break;
        case 3:
            $turfraction = 0.33333;
            break;
        case 4:
            $turfraction = 0.25;
            break;
        case 5:
            $turfraction = 0.20;
            break;
        case 10:
            $turfraction = 0.1;
            break;
        default:
            print($numAnswers . '-> Illegal number!');
    }
    return $turfraction;
}