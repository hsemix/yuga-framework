<?php
/**
 * @author Mahad Tech Solutions
 */
namespace Yuga\Models;

use Yuga\Database\Elegant\Model;
use Yuga\Shared\Controller as Universal;

class ElegantModel extends Model
{
    /**
     * @param \array $options
     * @return null
     */
    public function __construct(array $options = [])
    {
        //$this->init();
        parent::__construct($options);
    }    
}