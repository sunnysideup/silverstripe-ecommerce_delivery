<?php
/**
 *@source: https://github.com/yetzt/boxing/blob/master/boxing.class.php
 */
class PackagingBox
{
    private $outer_boxes;

    private $inner_boxes;

    public function boxing()
    {
        $this->outer_boxes = [];
        $this->inner_boxes = [];
    }

    public function add_outer_box($l, $w, $h)
    {
        if ($l > 0 && $w > 0 && $h > 0) {
            $this->outer_boxes[] = [
                'dimensions' => $this->sort_dimensions($l, $w, $h),
                'packed' => false,
            ];
        }

        return true;
    }

    public function add_inner_box($l, $w, $h)
    {
        if ($l > 0 && $w > 0 && $h > 0) {
            $this->inner_boxes[] = [
                'dimensions' => $this->sort_dimensions($l, $w, $h),
                'packed' => false,
            ];
        }

        return true;
    }

    public function fits()
    {
        // first we do a simple volume check, this can save a lot of calculations
        if (! $this->fits_volume()) {
            return false;
        }

        // get next inner box
        while (true) {
            $inner_box_id = $this->next_inner_box();
            if (false === $inner_box_id) {
                break;
            }

            $this->sort_outer_boxes(); // smallest first
            $found_fitting_box = false;
            foreach ($this->outer_boxes as $outer_box_id => $outer_box) {
                if (! $outer_box['packed'] && $this->fits_inside($inner_box_id, $outer_box_id)) {
                    // matches!
                    $this->inner_boxes[$inner_box_id]['packed'] = true;
                    $this->outer_boxes[$outer_box_id]['packed'] = true;
                    $this->find_subboxes($inner_box_id, $outer_box_id);
                    $found_fitting_box = true;

                    break;
                }
            }

            if (! $found_fitting_box) {
                return false;
            }
        }

        // we ran out of inner boxes but have outer boxes left
        return true;
    }

    public function fits_volume()
    {
        $inner_volume = 0;
        $outer_volume = 0;
        foreach ($this->inner_boxes as $inner) {
            $inner_volume += ($inner['dimensions'][0] * $inner['dimensions'][1] * $inner['dimensions'][2]);
        }

        foreach ($this->outer_boxes as $outer) {
            $outer_volume += ($outer['dimensions'][0] * $outer['dimensions'][1] * $outer['dimensions'][2]);
        }

        // inner boxes have more volume than outer ones
        return $inner_volume <= $outer_volume;
    }

    public function diffsort($array)
    {
        // quick and dirty hack since sksort() does strange things
        $tmp_array = [];
        foreach ($array as $item) {
            $tmp_array[$item['diff']][] = $item;
        }

        krsort($tmp_array);
        $array = [];
        foreach ($tmp_array as $a) {
            foreach ($a as $item) {
                $array[] = $item;
            }
        }

        return $array;
    }

    private function find_subboxes($inner_box_id, $outer_box_id)
    {
        $inner_dimensions = $this->inner_boxes[$inner_box_id]['dimensions'];
        $outer_dimensions = $this->outer_boxes[$outer_box_id]['dimensions'];
        sort($outer_dimensions);
        $pairs = [];
        $unset = 'nothing';
        foreach ($inner_dimensions as $inner_value) {
            foreach ($outer_dimensions as $outer_id => $outer_value) {
                if ($inner_value <= $outer_value) {
                    $unset = $outer_id;
                    $pairs[] = [
                        'inner' => $inner_value,
                        'outer' => $outer_value,
                        'diff' => $outer_value - $inner_value,
                    ];

                    break;
                }
            }

            unset($outer_dimensions[$unset]);
        }

        do {
            $pairs = $this->diffsort($pairs);
            $this->add_outer_box($pairs[0]['diff'], $pairs[1]['outer'], $pairs[2]['outer']);
            $pairs[0]['diff'] = 0;
            $pairs[0]['outer'] = $pairs[0]['inner'];
        } while ($pairs[0]['diff'] > 0 || $pairs[1]['diff'] > 0 || $pairs[2]['diff'] > 0);

        return true;
    }

    private function fits_inside($inner_box_id, $outer_box_id)
    {
        // fits
        // fits not
        return $this->inner_boxes[$inner_box_id]['dimensions'][0] <= $this->outer_boxes[$outer_box_id]['dimensions'][0] &&
            $this->inner_boxes[$inner_box_id]['dimensions'][1] <= $this->outer_boxes[$outer_box_id]['dimensions'][1] &&
            $this->inner_boxes[$inner_box_id]['dimensions'][2] <= $this->outer_boxes[$outer_box_id]['dimensions'][2];
    }

    private function sort_dimensions($l, $w, $h)
    {
        $dimensions = [$l, $w, $h];
        rsort($dimensions);

        return $dimensions;
    }

    private function sort_outer_boxes()
    {
        foreach ($this->outer_boxes as $k => $v) {
            $this->outer_boxes[$k]['longest_side'] = $v['dimensions'][0];
        }

        $this->outer_boxes = $this->sksort($this->outer_boxes, 'longest_side', false, true);

        return true;
    }

    private function next_inner_box()
    {
        $biggest_size = 0;
        $biggest_id = false;
        foreach ($this->inner_boxes as $id => $box) {
            if (! $box['packed'] && $box['dimensions'][0] > $biggest_size) {
                $biggest_size = $box['dimensions'][0];
                $biggest_id = $id;
            }
        }

        return $biggest_id;
    }

    private function sksort($array, $subkey, $sort_descending = false, $keep_keys_in_sub = false)
    {
        // slightly modified since stolen from http://www.php.net/manual/de/function.sort.php#93473
        foreach ($array as &$value) {
            $sort = [];
            foreach ($value as $index => $val) {
                $sort[$index] = $val[$subkey];
            }

            asort($sort);
            $keys = array_keys($sort);
            $new_value = [];
            foreach ($keys as $index) {
                if ($keep_keys_in_sub) {
                    $new_value[$index] = $value[$index];
                } else {
                    $new_value[] = $value[$index];
                }
            }

            $value = $sort_descending ? array_reverse($new_value, $keep_keys_in_sub) : $new_value;
        }

        return $array;
    }
}
