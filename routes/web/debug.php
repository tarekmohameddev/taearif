<?php

/*
|--------------------------------------------------------------------------
| Debug Routes
|--------------------------------------------------------------------------
| Routes for debugging and development purposes
|
*/

// Test sales route
Route::get('/test-sales', function () {
    return Sale::with('property', 'user', 'contract')->get();
});

// All routes listing (for debugging)
Route::get('/all-routes', function () {
    // Get the 'type' parameter to decide which routes to show (web, api, or all)
    $type = request()->get('type', 'all'); // Default to 'all' if not specified

    // Filter routes based on the selected type
    $routes = collect(Route::getRoutes())->filter(function ($route) use ($type) {
        if ($type == 'web') {
            return in_array('web', $route->middleware());
        } elseif ($type == 'api') {
            return in_array('api', $route->middleware());
        }
        // If 'all' is selected, include both 'web' and 'api' routes
        return in_array('web', $route->middleware()) || in_array('api', $route->middleware());
    });

    // Sort routes to have GET methods at the top
    $sortedRoutes = $routes->sortByDesc(function ($route) {
        return in_array('GET', $route->methods());
    });

    echo "<!DOCTYPE html>
    <html>
    <head>
        <title>All Routes</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; }
            table { width: 100%; border-collapse: collapse; margin-top: 20px; }
            th, td { padding: 12px; border: 1px solid #ddd; text-align: left; }
            th { background-color: #f2f2f2; }
            a { text-decoration: none; color: blue; }
            a:hover { text-decoration: underline; }
        </style>
    </head>
    <body>
        <h1>All Routes</h1>
        <p><a href='/all-routes?type=web'>Web Routes</a> | <a href='/all-routes?type=api'>API Routes</a> | <a href='/all-routes?type=all'>All Routes</a></p>
        <table>
            <tr>
                <th>URI</th>
                <th>Name</th>
                <th>Methods</th>
                <th>Action</th>
            </tr>";

    foreach ($sortedRoutes as $route) {
        $methods = implode(' | ', $route->methods());
        $uri = $route->uri();
        $name = $route->getName() ?? 'N/A';
        $action = $route->getActionName();

        // Extract the method name if the action contains '@'
        if (strpos($action, '@') !== false) {
            $actionParts = explode('@', $action);
            $method = $actionParts[1];
            $actionDisplay = '@' . $method;
        } else {
            $actionDisplay = 'Closure';
        }

        echo "<tr>
                <td><a href='/$uri' target='_blank'>/$uri</a></td>
                <td>$name</td>
                <td>$methods</td>
                <td>$actionDisplay</td>
            </tr>";
    }

    echo "</table>
    </body>
    </html>";
});

// Google OAuth debug route
Route::get('/debug/google', function () {
    return Socialite::driver('google')->redirect();
});
