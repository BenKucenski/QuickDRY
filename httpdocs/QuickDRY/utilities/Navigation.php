<?php

/**
 * Class Navigation
 */
class Navigation
{
    private $_PERMISSIONS = [];
    private $_MENU = [];
    /**
     * @param $_ADD
     */
    public function Combine($_ADD)
    {
        foreach ($_ADD as $link) {
            if(!in_array($link, $this->_PERMISSIONS)) {
                $this->_PERMISSIONS[] = $link;
            }
        }
    }

    public function SetMenu($menu)
    {
        $this->_MENU = $menu;
    }

    /**
     * @param string $_CUR_PAGE
     * @param bool $test
     * @return bool
     */
    public function CheckPermissions($_CUR_PAGE, $test = false)
    {
        if ($_CUR_PAGE == '/' || $_CUR_PAGE == '' || $_CUR_PAGE == '/')
            return true;

        if(is_array($_CUR_PAGE)) {
            Halt($_CUR_PAGE);
        }

        $t = explode('/', $_CUR_PAGE);
        if (stristr($t[sizeof($t) - 1],'json') !== FALSE) {
            return true;
        }

        if(in_array($_CUR_PAGE, $this->_PERMISSIONS)) {
            return true;
        }

        if (!$test) {
            HTTP::RedirectError('You do not have permission to view that page');
        }
        return false;
    }

    /**
     * @param $_MENU
     * @return string
     */
    public function RenderBootstrap($_MENU = null)
    {
        if($_MENU) {
            $this->_MENU = $_MENU;
        }

        $_MENU_HTML = '';
        foreach ($this->_MENU AS $name => $values) {
            if(isset($values['link']) && !$this->CheckPermissions($values['link'], true)) {
                continue;
            }

            $has_visible = false;
            if (isset($values['links']) && sizeof($values['links'])) {
                foreach ($values['links'] as $link_name => $url) {
                    if (isset($url['link']) && strcasecmp($url['link'], $name) == 0) {
                        continue;
                    }

                    if(!isset($url['link'])) {
                        if(!$this->CheckPermissions($url, true)) {
                            continue;
                        }
                    } else {
                        if (!$this->CheckPermissions($url['link'], true)) {
                            continue;
                        }
                    }

                    $has_visible = true;
                    break;
                }
            }

            if ($has_visible) {
                $_MENU_HTML .= '<li class="dropdown"><a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">' . $name . '<span class="caret"></span></a>';
                ksort($values['links']);
                reset($values['links']);
                $_MENU_HTML .= '<ul class="dropdown-menu">';
                foreach ($values['links'] as $link_name => $url) {
                    if (!is_array($url)) {
                        if(!$this->CheckPermissions($url, true)) {
                            continue;
                        }
                        $_MENU_HTML .= '<li><a href="' . $url . '">' . $link_name . '</a></li>' . PHP_EOL;
                    } else {
                        if (isset($url['onclick'])) {
                            $_MENU_HTML .= '<li><a href="#" onclick="' . $url['onclick'] . '">' . $name . '</a></li>';
                        }

                        if (isset($url['links']) && sizeof($url['links'])) {

                            $_MENU_HTML .= '<li class="dropdown"><a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">' . $name . '<span class="caret"></span></a>';
                            $_MENU_HTML .= '<ul class="dropdown-menu">' . PHP_EOL;
                            foreach ($url['links'] as $sub_name => $sub_url) {
                                if($this->CheckPermissions($sub_url, true)) {
                                    $_MENU_HTML .= '<li><a href="' . $sub_url . '">' . $sub_name . '</a></li>' . PHP_EOL;
                                }
                            }
                            $_MENU_HTML .= '</ul>' . PHP_EOL;
                        } else {
                            if (isset($url['onclick'])) {
                                $_MENU_HTML .= '<li><a href="#" onclick="' . $url['onclick'] . '">' . $name . '</a></li>';
                            } else {
                                if (isset($url['link'])) {
                                    if (!$this->CheckPermissions($url['link'], true)) {
                                        continue;
                                    }

                                    $_MENU_HTML .= '<li><a href="' . $url['link'] . '">' . $link_name . '</a></li>' . PHP_EOL;
                                }
                            }
                        }
                    }
                }
                $_MENU_HTML .= '</ul></li>' . PHP_EOL;
            } else {
                if (isset($values['onclick'])) {
                    $_MENU_HTML .= '<li><a href="#" onclick="' . $values['onclick'] . '">' . $name . '</a></li>';
                } else {
                    if (isset($values['link'])) {
                        if ($this->CheckPermissions($values['link'], true)) {
                            $_MENU_HTML .= '<li><a href="' . $values['link'] . '"><b>' . $name . '</b></a></li>' . PHP_EOL;
                        }
                    }
                }
            }
        }
        return $_MENU_HTML;
    }
}