<?php

/**
 * Class FormClass
 */
class FormClass
{
    public static function Options()
    {
        return static::$_options;
    }

    /**
     * @param $id
     *
     * @return null
     */
    public static function Get($id)
    {
        return isset(static::$_options[$id]) ? static::$_options[$id] : null;
    }

    /**
     * @param $options
     * @param $selected
     * @param $id
     * @param string $class
     * @param string $onchange
     * @return string
     */
    public static function SelectItems($options, $selected, $id, $class = '', $onchange='', $add_none = false)
    {
        if (!is_array($id)) {
            $name = $id;
        } else {
            $name = $id['name'];
            $id = $id['id'];
        }

        $res = '<select onchange="' . $onchange . '" class="' . $class . '" name="' . $name . '" id="' . $id . '">';
        if($add_none) {
            $res .= '<option value="">Select One...</input>';
        }
        foreach ($options as $id => $disp) {
            if ($id == $selected) {
                $res .= '<option selected value="' . $id . '">' . $disp . '</input>';
            } else {
                $res .= '<option value="' . $id . '">' . $disp . '</input>';
            }
        }
        $res .= '</select>';

        return $res;

    }

    /**
     * @param $val
     * @param $id
     * @param null $outer_style
     * @param null $inner_style
     * @return string
     */
    public static function Textarea($val, $id, $outer_style = null, $inner_style = null)
    {
        if (is_array($id)) {
            $name = $id['name'];
            $id = $id['id'];
        } else {
            $name = $id;
        }

        return
            '<div id="' . $id . '_div" style="' . $outer_style . '"><textarea style="' . $inner_style . '" name="' . $name . '" id="' . $id
            . '">' . $val . '</textarea></div>';
    }

    /**
     * @param $val
     * @param $id
     * @param null $outer_style
     * @param null $inner_style
     * @return string
     */
    public static function Text($val, $id, $outer_style = null, $inner_style = null)
    {
        if (is_array($id)) {
            $name = $id['name'];
            $id = $id['id'];
        } else {
            $name = $id;
        }

        return '<div id="' . $id . '_div" style="' . $outer_style . '"><input type="text" style="' . $inner_style . '" name="' . $name
        . '" id="' . $id . '" value="' . $val . '" /></div>';
    }

    /**
     * @param $selected
     * @param $options
     * @param $id
     * @param null $outer_class
     * @param null $inner_style
     * @param bool $new_line
     * @param string $onchange
     * @return string
     */
    public static function Checkbox($selected, $options, $id, $outer_class = null, $inner_style = null, $new_line = false, $onchange = '')
    {
        if (!is_array($selected)) {
            $selected = explode(',', $selected);
        }

        if (is_array($id)) {
            $name = $id['name'];
            $id = $id['id'];
        } else {
            $name = $id;
        }

        if($name) {
            $name .= '[]';
        }

        $res = '<div id="' . $id . '_div" class="' . $outer_class . '">';
        foreach ($options as $i => $option) {
            if (in_array($option, $selected)) {
                $res .= '<label><input class="' . $id . '_checked" checked="checked" onchange="' . $onchange . '" type="checkbox" style="' . $inner_style . '" name="' . $name . '" id="' . $id . '_' . $i
                    . '" value="' . $option . '" />' . $option . '</label>';
            } else {
                $res .= '<label><input class="' . $id . '_checked" type="checkbox" onchange="' . $onchange . '" style="' . $inner_style . '" name="' . $name . '" id="' . $id . '_' . $i . '" value="'
                    . $option . '" />' . $option . '</label>';
            }
            if ($new_line) {
                $res .= '<br/>';
            }
        }

        return $res . '</div>';
    }

    /**
     * @param $selected
     * @param $options
     * @param $id
     * @param null $outer_style
     * @param null $inner_style
     * @param bool $new_line
     * @return string
     */
    public static function Radio($selected, $options, $id, $outer_style = null, $inner_style = null, $new_line = false)
    {
        if (!is_array($selected)) {
            $selected = explode(',', $selected);
        }

        if (is_array($id)) {
            $name = $id['name'];
            $id = $id['id'];
        } else {
            $name = $id;
        }

        $res = '<div id="' . $id . '_div" style="' . $outer_style . '">';
        foreach ($options as $i => $option) {
            if (in_array($option, $selected)) {
                $res .= '<input checked="checked" type="radio" style="' . $inner_style . '" name="' . $name . '" id="' . $id . '_' . $i
                    . '" value="' . $option . '" />' . $option;
            } else {
                $res .=
                    '<input type="radio" style="' . $inner_style . '" name="' . $name . '" id="' . $id . '_' . $i . '" value="' . $option
                    . '" />' . $option;
            }
            if ($new_line) {
                $res .= '<br/>';
            }
        }

        return $res . '</div>';
    }
}