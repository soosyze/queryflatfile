<?php

namespace Queryjson;

class Where
{

    protected $where = [];

    public function where( $column, $condition, $value = null )
    {
        if( is_null( $value ) )
        {
            $value     = $condition;
            $condition = '==';
        }
        $this->where[] = [ 'type' => __FUNCTION__, 'column' => $column, 'condition' => $condition,
            'value' => $value];
        return $this;
    }

    public function bwetween( $column, $min, $max )
    {
        $this->where[] = [ 'type' => __FUNCTION__, 'column' => $column, 'min' => $min,
            'max' => $max];
        return $this;
    }

    public function in( $column, array $values )
    {
        $this->where[] = [ 'type' => __FUNCTION__, 'column' => $column, 'values' => $values];
        return $this;
    }

    public function isEmpty( $column, $bool = true )
    {
        $this->where[] = [ 'type' => __FUNCTION__, 'column' => $column, 'bool' => $bool];
        return $this;
    }

    public function regex( $column, $pattern )
    {
        $this->where[] = [ 'type' => __FUNCTION__, 'column' => $column, 'values' => $pattern];
        return $this;
    }

    public function wAND()
    {
        $this->where[] = [ 'type' => __FUNCTION__];
        return $this;
    }

    public function wOR()
    {
        $this->where[] = [ 'type' => __FUNCTION__];
        return $this;
    }

    private function isColumn( $value )
    {
        return strstr( $value, '.' );
    }

    private function getTable( $value )
    {
        return strstr( $value, '.' );
    }

    private function getColumn( $value )
    {
        return strrchr( $value, '.' )
            ? substr( strrchr( $value, '.' ), 1 )
            : $value;
    }

    private function evalWhere( $value )
    {
        return $this->isColumn( $value )
            ? '$rowTableJoin[' . "'" . $this->getColumn( $value ) . "'" . ']  '
            : $value;
    }

    public function getColumns()
    {
        $output = [];
        foreach( $this->where as $value )
        {
            if( isset( $value[ 'column' ] ) )
            {
                $output[] = $this->getColumn( $value[ 'column' ] );
            }
        }
        return $output;
    }

    public function execute()
    {
        $evalWhere = '';
        foreach( $this->where as $key => $values )
        {
            //selon le type de condition je concaténe ma chaine d'évaluation
            switch( $values[ 'type' ] )
            {
                case 'where':
                    $evalWhere .= '($row[' . "'" . $values[ 'column' ] . "'" . '] '
                        . $values[ 'condition' ] . ' '
                        . '"' . $values[ 'value' ] . '")';
                    break;
                case 'bwetween':
                    $evalWhere .= '($row[' . "'" . $values[ 'column' ] . "'" . '] '
                        . '> '
                        . $values[ 'min' ] . ' '
                        . 'AND '
                        . '$row[' . "'" . $values[ 'column' ] . "'" . '] '
                        . '< '
                        . $values[ 'max' ] . ') ';
                    break;
                case 'in':
                    foreach( $values[ 'values' ] as $key => $value )
                    {
                        $evalWhere .= '($row[' . "'" . $values[ 'column' ] . "'" . ']'
                            . '=='
                            . $value . ')';
                        if( count( $values[ 'values' ] ) != $key + 1 )
                        {
                            $evalWhere .= '||';
                        }
                    }
                    break;
                case 'isEmpty':
                    $evalWhere .= '( empty($row[' . "'" . $values[ 'column' ] . "'" . ']) )';
                    break;
                case 'regex':
                    $evalWhere .= '( preg_match("' . $values[ 'values' ] . '", $row[' . "'" . $values[ 'column' ] . "'" . ']) )';
                    break;
                case 'wAND':
                    $evalWhere .= ' && ';
                    break;
                case 'wOR':
                    $evalWhere .= ' || ';
                    break;
            }
        }
        return $evalWhere;
    }

    public function executeJoin()
    {
        $evalWhere = '';
        foreach( $this->where as $values )
        {
            //selon le type de condition je concaténe ma chaine d'évaluation
            switch( $values[ 'type' ] )
            {
                case 'where':
                    $evalWhere .= '($rowTable[' . "'" . $values[ 'column' ] . "'" . '] '
                        . $values[ 'condition' ] . ' '
                        . $this->evalWhere( $values[ 'value' ] ) . ')';
                    break;
                case 'wAND':
                    $evalWhere .= ' && ';
                    break;
                case 'wOR':
                    $evalWhere .= ' || ';
                    break;
            }
        }
        unset( $this->where );
        return $evalWhere;
    }

}
