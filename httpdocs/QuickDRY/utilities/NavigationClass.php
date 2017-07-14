<?php

/**
 * Class NavigationClass
 */
class NavigationClass
{
    private $_MENU = [];

    /**
     * @param $_ADD
     */
    public function Combine($_ADD)
    {
        foreach ($_ADD as $key => $settings) {
            if (isset($settings['onclick']))
                $this->_MENU[$key]['onclick'] = $settings['onclick'];
            if (isset($settings['link']))
                $this->_MENU[$key]['link'] = $settings['link'];
            if (isset($settings['display']))
                $this->_MENU[$key]['display'] = $settings['display'];

            if (isset($settings['links']))
                foreach ($settings['links'] as $new_name => $new_url) {
                    $found = false;
                    if (isset($this->_MENU[$key]['links']))
                        foreach ($this->_MENU[$key]['links'] as $name => $url)
                            if ($name == $new_name)
                                $found = true;
                    if (!$found)
                        $this->_MENU[$key]['links'][$new_name] = $new_url;
                }
        }
    }

    /**
     * @param $_CUR_PAGE
     * @param bool $test
     * @return bool
     */
    public function CheckPermissions($_CUR_PAGE, $test = false)
    {
        if ($_CUR_PAGE == '/' || $_CUR_PAGE == '' || $_CUR_PAGE == '/')
            return true;

        $t = explode('.', $_CUR_PAGE);
        if ($t[sizeof($t) - 1] === 'json') {
            return true;
        }

        foreach ($this->_MENU AS $name => $values) {
            if (isset($values['link'])) {
                if (strcasecmp($values['link'], $_CUR_PAGE) == 0)
                    return true;
            }

            if (isset($values['links']))
                foreach ($values['links'] as $name2 => $url) {
                    if (!is_array($url)) {
                        if (strcasecmp($url, $_CUR_PAGE) == 0)
                            return true;
                    } else {
                        if (isset($url['link'])) {
                            if (strcasecmp($url['link'], $_CUR_PAGE) == 0)
                                return true;
                        }
                        if (isset($url['links']) && is_array($url['links'])) {
                            foreach ($url['links'] as $name3 => $url2) {
                                if (strcasecmp($url2, $_CUR_PAGE) == 0)
                                    return true;
                            }
                        }
                    }
                }
        }
        if (!$test) {
            Debug([$_CUR_PAGE, $this->_MENU]);
            RedirectError('You do not have permission to view that page');
        }
        return false;
    }

    /**
     * @return string
     */
    public function RenderBootstrap()
    {
        $_MENU_HTML = '';
        foreach ($this->_MENU AS $name => $values) {
            if (isset($values['display']) && strcmp($values['display'], 'none') == 0)
                continue;

            if (isset($values['link']) && strcasecmp($values['link'], $name) == 0)
                continue;

            $has_visible = false;
            if (isset($values['links']) && sizeof($values['links'])) {
                foreach ($values['links'] as $link_name => $url) {
                    if (strcasecmp($url, $link_name) == 0) {
                        continue;
                    } else {
                        if (isset($url['display']) && $url['display'] === 'none') {
                            continue;
                        } else {
                            $has_visible = true;
                            break;
                        }
                    }
                }
            }

            if ($has_visible) {
                $_MENU_HTML .= '<li class="dropdown"><a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">' . $name . '<span class="caret"></span></a>';
                ksort($values['links']);
                reset($values['links']);
                $_MENU_HTML .= '<ul class="dropdown-menu">';
                foreach ($values['links'] as $link_name => $url) {
                    if (!is_array($url)) {
                        if (strcasecmp($name, $url) != 0) {
                            $_MENU_HTML .= '<li><a href="' . $url . '">' . $link_name . '</a></li>' . PHP_EOL;
                        }
                    } else {
                        if (isset($url['onclick'])) {
                            $_MENU_HTML .= '<li><a href="#" onclick="' . $url['onclick'] . '">' . $name . '</a></li>';
                        } else {

                            if (isset($url['link']) && strcasecmp($url['link'], $name) == 0) {
                                continue;
                            }
                        }

                        if (isset($url['links']) && sizeof($url['links'])) {

                            $_MENU_HTML .= '<li class="dropdown"><a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">' . $name . '<span class="caret"></span></a>';
                            $_MENU_HTML .= '<ul class="dropdown-menu">' . PHP_EOL;
                            foreach ($url['links'] as $sub_name => $sub_url) {
                                if (strcasecmp($sub_name, $sub_url) == 0) {
                                    continue;
                                }

                                $_MENU_HTML .= '<li><a href="' . $sub_url . '">' . $sub_name . '</a></li>' . PHP_EOL;
                            }
                            $_MENU_HTML .= '</ul>' . PHP_EOL;
                        } else {
                            if (isset($url['onclick'])) {
                                $_MENU_HTML .= '<li><a href="#" onclick="' . $url['onclick'] . '">' . $name . '</a></li>';
                            } else {
                                if (isset($url['link'])) {
                                    if (strcasecmp($url['link'], $link_name) != 0) {
                                        $_MENU_HTML .= '<li><a href="' . $url['link'] . '">' . $link_name . '</a></li>' . PHP_EOL;
                                    } else {
                                        continue;
                                    }
                                } else {
                                    continue;
                                }
                            }
                        }
                        //$_MENU_HTML .= '<!-- --></li>' . PHP_EOL;
                    }
                }
                $_MENU_HTML .= '</ul></li>' . PHP_EOL;
            } else {
                $_MENU_HTML .= '<li>';
                if (isset($values['onclick'])) {
                    $_MENU_HTML .= '<a href="#" onclick="' . $values['onclick'] . '">' . $name . '</a>';
                } else {
                    if (isset($values['link'])) {
                        $_MENU_HTML .= '<a href="' . $values['link'] . '"><b>' . $name . '</b></a>' . PHP_EOL;
                    } else {
                        $_MENU_HTML .= '<a href="#"><b>' . $name . '</b></a>' . PHP_EOL;
                    }
                    $_MENU_HTML .= '</li>';
                }

            }
        }
        return $_MENU_HTML;
    }
}