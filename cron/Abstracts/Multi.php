<?namespace Cron\Abstracts;

use App\Interfaces\Config;

abstract class Multi extends Cron
{
    private $attributes;

    protected function prepare(Config $config)
    {
        $this->attributes = $config->getOptions();
    }

    public function exec()
    {
        return $this->getAttributes();
    }

    public function getAttributes()
    {
        return $this->attributes;
    }
}