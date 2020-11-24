<?php
declare(strict_types=1);

namespace App\Achievements;

use Assada\Achievements\Achievement;

class UserUploaded200Subtitles extends Achievement
{
    /*
     * The achievement name
     */
    public string $name = 'UserUploaded200Subtitles';

    /*
     * A small description for the achievement
     */
    public string $description = 'You have made 200 subtitle uploads!';

    /*
    * The amount of "points" this user need to obtain in order to complete this achievement
    */
    public int $points = 200;
}
