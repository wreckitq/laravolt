<?php

declare(strict_types=1);

namespace Laravolt\Platform\Services;

use Illuminate\Support\Facades\Route;

class MenuBuilder
{
    protected $defaultIcon;

    public function __construct()
    {
        $this->defaultIcon = config('laravolt.ui.default_menu_icon');
    }

    public function make()
    {
        $args = func_get_args();
        $num_args = func_num_args();

        if (is_array($args[0])) {
            if ($num_args === 2) {
                return $this->loadSection('', $args[0], $args[1]);
            } elseif ($num_args === 1) {
                return $this->loadSection('', $args[0]);
            }
        }

        if (is_string($args[0])) {
            if ($num_args === 3) {
                return $this->loadSection($args[0], $args[1], $args[2]);
            }
            if ($num_args === 2) {
                return $this->loadSection($args[0], $args[1]);
            }
        }
    }

    public function loadSection($name, $menu, $options = [])
    {
        app('laravolt.menu.sidebar')->register(
            function ($menu) use ($name, $options) {
                $section = $menu->add($name);
                $this->setData($section, $options);
                $this->addMenu($section, $menu);
            }
        );
    }

    public function loadArray(array $menu)
    {
        $order = null;
        $menu = collect($menu)->reject(fn ($item) => !($item['menu'] ?? null));
        foreach ($menu as $title => $option) {
            if ($order === null) {
                $order = $option['order'] ?? 50;
            }

            app('laravolt.menu.sidebar')->register(
                function ($menu) use ($title, $option, $order) {
                    /** @var \Lavary\Menu\Builder $section */
                    $section = $menu->get(strtolower(trim($title)));
                    if ($section === null) {
                        $url = $this->generateUrl($option);
                        $section = $menu
                            ->add($title, $url)
                            ->data('order', $order);
                    }

                    $section->data('icon', $option['icon'] ?? $this->defaultIcon);
                    $section->data('permissions', $option['permissions'] ?? null);

                    if (isset($option['menu'])) {
                        $this->addMenu($section, $option['menu']);
                    }
                    if (isset($option['data'])) {
                        $this->setData($section, $option['data']);
                    }
                }
            );
            $order++;
        }
    }

    private function addMenu(&$parent, $menus)
    {
        foreach ($menus as $name => $option) {
            if (is_string($option)) {
                $parent->add($name, $option);
                continue;
            }

            if (!isset($option['menu'])) {
                $menu = $parent->add($name, $this->generateUrl($option));
                if (isset($option['active'])) {
                    $menu->active($option['active']);
                }
            } else {
                $menu = $parent->add($name, '#');
                $this->addMenu($menu, $option['menu']);
            }
            if (isset($option['data'])) {
                $this->setData($menu, $option['data']);
            }

            $menu->data('icon', $option['icon'] ?? $this->defaultIcon);
            $menu->data('permissions', $option['permissions'] ?? null);
        }
    }

    private function setData(&$menu, $data): void
    {
        foreach ($data as $key => $value) {
            $menu->data($key, $value);
        }
    }

    private function generateUrl($option): string
    {
        $route = $option['route'] ?? null;

        if ($route === null) {
            return url($option['url'] ?? '#');
        }

        if (is_string($route) && Route::has($route)) {
            return route($route);
        }

        if (is_array($route)) {
            [$routeName, $param] = $route;

            if (Route::has($routeName)) {
                return route($routeName, $param);
            }
        }

        return '#';
    }
}
