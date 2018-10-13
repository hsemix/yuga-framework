<?php
/**
 * @author Mahad Tech Solutions
 */
namespace Yuga\Models;

use Yuga\Database\Elegant\Model;

class ElegantModel extends Model
{
    /**
     * @param \array $options
     * @return null
     */
    public function __construct(array $options = [])
    {
        parent::__construct($options);
    }    
}