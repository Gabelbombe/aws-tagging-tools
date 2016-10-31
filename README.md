##### How to run

 - `cd /tmp && git clone https://github.com/ehime/aws-tagging-tools.git tagging ; cd $_`
 - `mkdir -p {output,data,vendor}`
 - `composer install`
 - Obtain your owners datafile from me!
 - `php -f runners/{toolname}.php`

Your folder should then look like the one below

![Directory Structure](https://github.com/ehime/aws-tagging-tools/blob/master/assets/dirstruct.png=444x245)

##### Scripts

 - [S3 Runner](https://github.com/ehime/aws-tagging-tools/blob/master/runners/s3-bucket.php)
 
 
##### Requirements

Scripts require my bash function `Alter`

 - [Alter](https://gist.github.com/ehime/11533e945c4e1eec3e13438592bb00f7)
 - [Composer](https://getcomposer.org/download/)