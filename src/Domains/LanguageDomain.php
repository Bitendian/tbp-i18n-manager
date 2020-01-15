<?php


namespace Bitendian\TBP\I18n\Domains;


use Bitendian\TBP\Domain\AbstractMysqlDomain;

class LanguageDomain extends AbstractMysqlDomain
{
    /**
     * @param string $name
     * @param string $locale
     * @return bool|int
     */
    public function insertLanguage($name, $locale)
    {
        $sql = 'INSERT INTO `Languages` (`Name`, `Locale`) VALUES (?, ?)';
        $params = [$name, $locale];

        return self::insertWithAutoincrement($this->connection->command($sql, $params));
    }
}