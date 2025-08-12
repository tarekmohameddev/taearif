<?php

namespace App\Console\Commands;

use Illuminate\Foundation\Console\RouteListCommand;
use Symfony\Component\Console\Input\InputOption;

class CustomRouteListCommand extends RouteListCommand
{
    protected $name = 'route:custom-list';

    protected $description = 'List all registered routes with customizable columns';

    protected function getOptions()
    {
        $options = parent::getOptions();
        $options[] = ['columns', null, InputOption::VALUE_OPTIONAL, 'Columns to display (domain,method,uri,name,action,middleware)', 'domain,method,uri,name,action,middleware'];

        return $options;
    }

    public function handle()
    {
        $columns = $this->option('columns') ? explode(',', $this->option('columns')) : ['domain', 'method', 'uri', 'name', 'action', 'middleware'];
        $validColumns = ['domain', 'method', 'uri', 'name', 'action', 'middleware'];
        $columns = array_intersect($columns, $validColumns); // Ensure only valid columns are used

        if (empty($columns)) {
            $this->error('No valid columns specified. Available columns: ' . implode(', ', $validColumns));
            return 1;
        }

        $routes = $this->getRoutes();
        $rows = [];

        foreach ($routes as $route) {
            $row = [];
            foreach ($columns as $column) {
                $row[] = $route[$column] ?? '';
            }
            $rows[] = $row;
        }

        $this->table($columns, $rows);
    }
}
