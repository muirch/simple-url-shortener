<?php

namespace Muir;

use PDO;


class Shortener
{
    private $db = [
        'type'     => 'mysql',
        'host'     => 'localhost',
        'port'     => '21',
        'encoding' => '',
        'name'     => 'shortener',
        'user'     => 'root',
        'pass'     => ''
    ];

    protected $stmt;

    private $error_url = 'https://www.sensou.me/';

    public function __construct()
    {
        $this->stmt = new PDO(
            $this->db['type'] .
            ':host=' . $this->db['host'] .
            ';port=' . $this->db['port'] .
            ';dbname=' . $this->db['name'],
            $this->db['user'],
            $this->db['pass']
        );
        if (!empty($this->db['encoding'])) {
            $this->stmt->exec("set names " . $this->db['encoding']);
        } else {
            $this->stmt->exec("set names utf8");
        }
    }

    public function throwError(?string $errorCode)
    {
        exit($errorCode);
    }

    public function setHeader(?string $url)
    {
        header("Location: $url", true, 301);
        exit();
    }

    public function query(?string $sql, array $params)
    {
        $stmt = $this->stmt->prepare($sql);
        if (!empty($params)) {
            foreach ($params as $key => $val) {
                if (is_int($val)) {
                    $type = PDO::PARAM_INT;
                } else {
                    $type = PDO::PARAM_STR;
                }
                $stmt->bindValue(':' . $key, $val, $type);
            }
        }
        $stmt->execute();
        return $stmt;
    }

    public function addVisitor(?string $url)
    {
        return $this->query('UPDATE urls SET url_visitors = url_visitors + 1 WHERE url_short = :url', $params = [
            'url' => $url
        ]);
    }

    public function findUrl(?string $url) : array {
        $result = $this->query('SELECT url_short, url_full FROM urls WHERE url_short = :url', $params = [
            'url' => $url
        ]);
        return $result->fetchAll(PDO::FETCH_ASSOC);
    }

    public function run()
    {
        $url = $_GET['p'];
        if (!empty($url) || isset($url)) {
            $result = $this->findUrl($url);
            if (!empty($result)) {
                if (!isset($_COOKIE[$url])) {
                    if (!$this->addVisitor($url)) {
                        $this->throwError(
                            'An error occur. Code: 1. Please contact dev@sensou.me'
                        );
                        return false;
                    }
                    setcookie($url, 1, time()+3600);
                }
                $this->setHeader($result[0]['url_full']);
            }
            $this->throwError(
                'Link not found!'
            );
            return false;
        }
        $this->throwError(
            'Too few arguments. Redirecting...'
        );
        $this->setHeader($this->error_url);
        return false;
    }
}