<?php
/**
 * Wasabi CMS
 * Copyright (c) Frank Förster (http://frankfoerster.com)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Frank Förster (http://frankfoerster.com)
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Wasabi\Core\Model\Table;

use Cake\Core\Exception\Exception;
use Cake\Network\Request;
use Cake\ORM\Query;
use Cake\ORM\Table;
use Wasabi\Core\Model\Entity\Slug;

class SluggedFiltersTable extends Table
{
    public function initialize(array $config)
    {
        $this->addBehavior('Timestamp');
    }

    public function findSlugByFilterData(Query $query, array $options)
    {
        if (!isset($options['request']) || get_class($options['request']) !== Request::class) {
            throw new Exception('The request query option must exist and must be of type Cake\Network\Request.');
        }
        if (!isset($options['filterData'])) {
            throw new Exception('No filterData option provided.');
        }
        $request = $options['request'];
        $filterData = $options['filterData'];
        return $query->select($this->alias() . '.slug')->where([
            'plugin' => $request->params['plugin'],
            'controller' => $request->params['controller'],
            'action' => $request->params['action'],
            'filter_data' => $this->_encodeFilterData($filterData)
        ]);
    }

    /**
     * Find a stored filter data combination for a given slug.
     *
     * @param Query $query
     * @param array $options
     * @return array
     */
    public function findFilterDataBySlug(Query $query, array $options)
    {
        if (!isset($options['request']) || get_class($options['request']) !== 'Request') {
            throw new Exception('The request query option must exist and must be of type Cake\Network\Request.');
        }
        $request = $options['request'];
        $encryptedFilterData = $query->select($this->alias() . '.filter_data')->where([
            'plugin' => $request->params['plugin'],
            'controller' => $request->params['controller'],
            'action' => $request->params['action'],
            'slug' => $request->params['sluggedFilter']
        ]);

        if ($encryptedFilterData) {
            return $this->_decodeFilterData($encryptedFilterData);
        }

        return [];
    }

    public function createSlugForFilterData(Request $request, array $filterData)
    {
        $charlist = 'abcdefghikmnopqrstuvwxyz';

        do {
            $slug = '';
            for ($i = 0; $i < 14; $i++) {
                $slug .= substr($charlist, rand(0, 31), 1);
            }
        } while (false !== $this->find('first')->select('slug')->where([
                'slug' => $slug,
                'plugin' => $request->params['plugin'],
                'controller' => $request->params['controller'],
                'action' => $request->params['action']
            ]));

        $slug = new Slug([
            'plugin' => $request->params['plugin'],
            'controller' => $request->params['controller'],
            'action' => $request->params['action'],
            'slug' => $slug,
            'filter_data' => $this->_encodeFilterData($filterData)
        ]);

        return $this->save($slug);
    }

    /**
     * Encode a filter data array to a string.
     *
     * @param array $filterData
     * @return string
     */
    protected function _encodeFilterData(array $filterData)
    {
        return json_encode($filterData);
    }

    /**
     * Decode a filter data string to the original filter data array.
     *
     * @param string $encodedFilterData
     * @return array
     */
    protected function _decodeFilterData($encodedFilterData)
    {
        return json_decode($encodedFilterData, true);
    }
}