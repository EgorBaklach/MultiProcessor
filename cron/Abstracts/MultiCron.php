<?namespace Cron\Abstracts;

abstract class MultiCron extends Cron
{
    public function exec()
    {
        return $this->getAttr();
    }
}