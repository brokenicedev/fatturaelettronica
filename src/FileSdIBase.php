<?php
namespace Brokenice\FatturaElettronica;

class FileSdIBase
{
    public $NomeFile = null;
    public $File = null;

    /**
     * @throws \Exception
     */
    public function __construct(\StdClass $parametersIn = null )
    {
        if ($parametersIn) {
            if (!property_exists($parametersIn, 'NomeFile')) {
                throw new \Exception("Cannot find property 'NomeFile'");
            }
            if (!property_exists($parametersIn, 'File')) {
                throw new \Exception("Cannot find property 'File'");
            }

            $this->NomeFile = $parametersIn->NomeFile;
            $this->File = $parametersIn->File;
            $this->removeBOM();
        }
    }

    public function __toString()
    {
        return "NomeFile:{$this->NomeFile}";
    }

    /**
     * Deprecated: use load()
     */
    public function import( $file )
    {
        return $this->load($file);
    }

    /**
     * @throws \Exception
     */
    public function load($invoice, $contents = null )
    {
        // Passing contents as param
        if (is_string($contents)) {
            $this->NomeFile = $invoice;
            $this->File = $contents;
            $this->removeBOM();
            return $this;
        }

        if (true === is_readable($invoice)) {
            $this->NomeFile = basename($invoice);
            $this->File = file_get_contents($invoice);
            $this->removeBOM();
        } else {
            throw new \Exception("Invalid file or object '$invoice'");
        }

        return $this;
    }

    /**
     * Remove UTF-8 BOM
     *
     * Credits: https://forum.italia.it/u/Francesco_Biegi
     * See https://forum.italia.it/t/risolto-notifica-di-scarto-content-is-not-allowed-in-prolog/5798/7
     */
    public function removeBOM()
    {
        $this->File = str_replace("\xEF\xBB\xBF", '', $this->File);

        return $this;
    }

}
