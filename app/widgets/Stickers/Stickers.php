<?php

use Moxl\Xec\Action\Message\Publish;
use Moxl\Xec\Action\BOB\Answer;

use Respect\Validation\Validator;

use Movim\Picture;

class Stickers extends \Movim\Widget\Base
{
    function load()
    {
        $this->addcss('stickers.css');
        $this->addjs('stickers.js');
        $this->registerEvent('bob', 'onRequest');
    }

    function onRequest($packet)
    {
        $content = $packet->content;

        $to = $content[0];
        $id = $content[1];
        $cid = $content[2];

        list($c, $ext) = explode('@', $cid);
        list($sh, $key) = explode('+', $c);

        $base64 = base64_encode(file_get_contents(CACHE_PATH.md5($key).'.png'));

        $a = new Answer;
        $a->setTo($to)
          ->setId($id)
          ->setCid($cid)
          ->setType('image/png')
          ->setBase64($base64)
          ->request();
    }

    function ajaxSend($to, $pack, $file, $muc = false)
    {
        if (!$this->validateJid($to)) return;

        list($key, $ext) = explode('.', $file);

        $filepath = dirname(__FILE__).'/stickers/'.$pack.'/'.$key.'.png';

        if (!file_exists($filepath)) return;

        // Caching the picture
        if (!file_exists(CACHE_PATH.md5($key).'.png')) {
            copy($filepath, CACHE_PATH.md5($key).'.png');
        }

        // Creating a message
        $m = new \App\Message;
        $m->user_id = $this->user->id;
        $m->jidto   = echapJid($to);
        $m->jidfrom = $this->user->id;
        $m->sticker = $key;
        $m->body    = $this->__('sticker.sent');

        $m->published = gmdate('Y-m-d H:i:s');

        $m->id      = generateUUID();
        $m->type    = 'chat';
        $m->resource = $this->user->session->resource;

        // Sending the sticker
        $html = "<p><img alt='Sticker' src='cid:sha1+".$key."@bob.xmpp.org'/></p>";

        $p = new Publish;
        $p->setTo($m->jidto)
          ->setContent($m->body)
          ->setHTML($html)
          ->setId($m->id);

        if ($muc) {
            $p->setMuc();
        }

        $p->request();

        $m->save();

        // Sending it to Chat
        if (!$p->getMuc()) {
            $packet = new Moxl\Xec\Payload\Packet;
            $packet->content = $m;
            $c = new Chat;
            $c->onMessage($packet);
        }
    }

    function ajaxShow($to, $pack = null)
    {
        if (!$this->validateJid($to)) return;

        $packs = $this->getPacks();

        $pack = isset($pack) ? $pack : current($packs);

        if (in_array($pack, $packs)) {
            $files = scandir(dirname(__FILE__).'/stickers/'.$pack);

            array_shift($files);
            array_shift($files);

            $view = $this->tpl();
            $view->assign('jid', $to);
            $view->assign('stickers', $files);
            $view->assign('packs', $packs);
            $view->assign('pack', $pack);
            $view->assign('info', parse_ini_file(dirname(__FILE__).'/stickers/'.$pack.'/info.ini'));
            $view->assign('path', $this->respath('stickers', false, false, true));

            Drawer::fill($view->draw('_stickers'), true);
        }
    }

    /**
     * @brief Show the smiley list
     */
    function ajaxSmiley($to)
    {
        if (!$this->validateJid($to)) return;

        $view = $this->tpl();
        $view->assign('jid', $to);
        $view->assign('packs', $this->getPacks());
        $view->assign('path', $this->respath('stickers', false, false, true));
        Drawer::fill($view->draw('_stickers_smiley'));
    }

    /**
     * @brief Show the smiley list
     */
    function ajaxSmileyTwo($to)
    {
        if (!$this->validateJid($to)) return;

        $view = $this->tpl();
        $view->assign('jid', $to);
        $view->assign('packs', $this->getPacks());
        $view->assign('path', $this->respath('stickers', false, false, true));
        Drawer::fill($view->draw('_stickers_smiley_two'));
    }

    /**
     * @brief Get the path of an emoji
     */
    function ajaxSmileyGet($string)
    {
        return prepareString($string);
    }

    /**
     * @brief Get a list of stickers packs
     */
    function getPacks()
    {
        $dirs = scandir(dirname(__FILE__).'/stickers/');

        $packs = [];

        array_shift($dirs);
        array_shift($dirs);

        // Get the packs
        foreach($dirs as $dir) {
            if (is_dir(dirname(__FILE__).'/stickers/'.$dir)) {
                array_push($packs, $dir);
            }
        }

        return $packs;
    }

    /**
     * @brief Validate the jid
     *
     * @param string $jid
     */
    private function validateJid($jid)
    {
        return Validator::stringType()->noWhitespace()
                        ->length(6, 60)->validate($jid);
    }

    function getSmileyPath($id)
    {
        return getSmileyPath($id);
    }
}
