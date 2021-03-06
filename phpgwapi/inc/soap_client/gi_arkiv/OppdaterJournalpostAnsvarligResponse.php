<?php

class OppdaterJournalpostAnsvarligResponse
{

    /**
     * @var Journalpost $return
     */
    protected $return = null;

    /**
     * @param Journalpost $return
     */
    public function __construct($return)
    {
      $this->return = $return;
    }

    /**
     * @return Journalpost
     */
    public function getReturn()
    {
      return $this->return;
    }

    /**
     * @param Journalpost $return
     * @return OppdaterJournalpostAnsvarligResponse
     */
    public function setReturn($return)
    {
      $this->return = $return;
      return $this;
    }

}
