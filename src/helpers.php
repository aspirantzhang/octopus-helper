<?php

function validateDateTime($date, $format = 'Y-m-d H:i:s')
{
    if ($date) {
        $d = DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) == $date;
    }
    return false;
}

function convertTime(string $timeString, string $format = 'Y-m-d H:i:s')
{
    $date = new \DateTime($timeString);
    return $date->format($format);
}

/* Recursive branch extrusion */
function createTreeBranch(array &$parents, array $children, int $depth = 0): array
{
    $tree = [];
    $depth++;
    foreach ($children as $child) {
        $child['depth'] = $depth;
        if (isset($parents[$child['id']])) {
            $child['children'] =
            createTreeBranch($parents, $parents[$child['id']], $depth);
        }
        $tree[] = $child;
    }
    return $tree;
}

/**
* convert Array to Tree
* @link https://stackoverflow.com/a/22020668/8819175
* @return array
*/
function arrayToTree(array $flat, int $root = 0): array
{
    if (isTreeArray($flat)) {
        // if parent_id not exist, set them to zero
        $allIds = array_column($flat, 'id');
        $flat = array_map(function ($row) use ($allIds) {
            // parent_id < 0 means self-config, ignore
            if ($row['parent_id'] > 0 && !in_array($row['parent_id'], $allIds)) {
                $row['parent_id'] = 0;
            }
            return $row;
        }, $flat);

        $parents = [];
        foreach ($flat as $a) {
            $parents[$a['parent_id']][] = $a;
        }
        
        // if root does not exist
        if (!isset($parents[$root])) {
            return [];
        }
        return createTreeBranch($parents, $parents[$root]);
    }
    return [];
}

function isTreeArray(array $array = []): bool
{
    if (is_array($array) && isset($array[0]['id']) && isset($array[0]['parent_id'])) {
        return true;
    }
    return false;
}

function isMultiArray(array $array): bool
{
    $multiCount = array_filter($array, 'is_array');
    return count($multiCount) > 0;
}

/**
 * Extract some key values ​​from the array.
 * TODO: rewrite & improve
 * @param array $array
 * @param string $targetKeyName
 * @param string $parentKeyName
 * @param bool $unique
 * @return array
 */
function extractValues(array $array = [], string $targetKeyName = 'id', string $parentKeyName = '', bool $unique = true): array
{
    if (empty($array)) {
        return [];
    }
    // Depth: level two
    if ($parentKeyName) {
        $result = [];
        foreach ($array as $value) {
            if (isset($value[$parentKeyName])) {
                if (isset($value[$parentKeyName][$targetKeyName])) {
                    $result[] = $value[$parentKeyName][$targetKeyName];
                } elseif (is_array($value[$parentKeyName])) {
                    $result = array_merge($result, array_column($value[$parentKeyName], $targetKeyName));
                }
            }
        }
        if (!$unique) {
            return $result;
        }
        if (isMultiArray($result)) {
            return array_unique($result, SORT_REGULAR);
        }
        return array_unique($result);
    }

    // Depth: level 1
    if (!$unique) {
        return array_column($array, $targetKeyName);
    }
    return array_unique(array_column($array, $targetKeyName), SORT_REGULAR);
}

function searchDescendantValueAggregation(string $keyName, string $elementKey, $elementValue, array $haystack, bool $deepSearch = true): array
{
    $currentElement = searchArrayByElement($elementValue, $elementKey, $haystack);

    if (!isset($currentElement['children'])) {
        if (isset($currentElement[$keyName])) {
            return [$currentElement[$keyName]];
        } else {
            return [];
        }
    }
    
    if (!$deepSearch) {
        return array_column($currentElement['children'], $keyName);
    }

    return recursiveSearchChildrenValue($keyName, $currentElement['children']);
}

function searchArrayByElement($value, string $key, array $haystack): array
{
    $iterator = new RecursiveIteratorIterator(new RecursiveArrayIterator($haystack));

    foreach ($iterator as $currentIteratorValue) {
        $subIterator = $iterator->getSubIterator();
        if (isset($subIterator[$key]) && $subIterator[$key] === $value) {
            return (iterator_to_array($subIterator));
        }
    }

    return [];
}

function recursiveSearchChildrenValue(string $needle, array $haystack): array
{
    $result = array_column($haystack, $needle);

    foreach ($haystack as $array) {
        if (isset($array['children']) && is_array($array['children'])) {
            $result = array_merge($result, recursiveSearchChildrenValue($needle, $array['children']));
        }
    }

    return $result;
}
