<?namespace Cron\Abstracts;

use App\Interfaces\Config;

abstract class Cron
{
    const TTL_FOREVER = 0; // навсегда
    const TTL_10MIN = 600; // 10 минут
    const TTL_HOUR = 3600; // 1 час
    const TTL_2HOURS = 3600 * 2; // 2 часа
    const TTL_DAY = 3600 * 24; // 1 день
    const TTL_MONTHS = 86400 * 30; // 1 месяц
    const TTL_2MONTHS = 86400 * 30 * 30; // 2 месяца

    public function __construct(Config $config)
    {
        if(method_exists($this, 'prepare'))
        {
            $this->prepare($config);
        }
    }

    abstract public function exec();
}