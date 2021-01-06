<?namespace Cron\Abstracts;

use App\Streams\Manager;
use App\Config;
use Helpers\Log;

abstract class Cron
{
    /** @var Manager */
    protected $manager;

    /** @var \Psr\SimpleCache\CacheInterface */
    protected $cache;

    /** @var array */
    protected $attr;

    const TTL_FOREVER = 0; // навсегда
    const TTL_10MIN = 600; // 10 минут
    const TTL_HOUR = 3600; // 1 час
    const TTL_2HOURS = 3600 * 2; // 2 часа
    const TTL_DAY = 3600 * 24; // 1 день
    const TTL_MONTHS = 86400 * 30; // 1 месяц
    const TTL_2MONTHS = 86400 * 30 * 30; // 2 месяца

    public function __construct(Config $config)
    {
        $this->manager = new Manager($config->getCommand(), $config->getPath());

        $this->cache = $config->getCache();
        $this->attr = $config->getAttr();

        if(method_exists($this, 'prepare'))
        {
            $this->prepare($config);
        }

        $this->cache->set($config->getHash(), 'Y', self::TTL_2MONTHS);
    }

    abstract public function exec();

    public function getAttr()
    {
        return $this->attr;
    }

    public static function done($manager, $stdout, $stderr)
    {
        if(!empty($stdout)) Log::add2log($stdout);
    }

    public static function fail($manager, $stdout, $stderr, $status)
    {
        if(!empty($stderr)) Log::add2log($stderr);
    }
}