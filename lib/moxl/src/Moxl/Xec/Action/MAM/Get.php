<?php

namespace Moxl\Xec\Action\MAM;

use Moxl\Xec\Action;
use Moxl\Stanza\MAM;

use Movim\Session;

class Get extends Action
{
    protected $_queryid;
    protected $_to;
    protected $_jid;
    protected $_start;
    protected $_end;
    protected $_limit;
    protected $_after;
    protected $_before;
    protected $_version = '2';

    public function request()
    {
        $sess = Session::start();

        // Generating the queryid key.
        $this->_queryid = \generateKey(12);
        $sess->set('mamid'.$this->_queryid, true);
        $this->store();

        MAM::get($this->_to, $this->_queryid, $this->_jid,
            $this->_start, $this->_end,
            $this->_limit, $this->_after, $this->_before,
            $this->_version);
    }

    public function setBefore($before = true)
    {
        $this->_before = $before;
        return $this;
    }

    public function handle($stanza, $parent = false)
    {
        $sess = Session::start();
        $sess->remove('mamid'.$this->_queryid);

        $this->pack($this->_to);
        $this->deliver();

        if(isset($stanza->fin)
        && (!isset($stanza->fin->attributes()->complete) || $stanza->fin->attributes()->complete != 'true')
        && isset($stanza->fin->set) && $stanza->fin->set->attributes()->xmlns == 'http://jabber.org/protocol/rsm'
        && isset($stanza->fin->set->last)) {
            $g = new Get;
            $g->setJid($this->_jid);
            $g->setTo($this->_to);
            $g->setLimit($this->_limit);
            $g->setStart($this->_start);
            $g->setEnd($this->_end);
            $g->setBefore($this->_before);
            $g->setVersion($this->_version);
            $g->setAfter((string)$stanza->fin->set->last);
            $g->request();
        }
    }
}
