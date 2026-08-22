<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// All agent users and all agents side by side
$users = \App\Models\User::where('is_agent', true)->get(['id', 'full_name', 'code']);
$agents = \App\Models\Agent::all(['id', 'code', 'name']);

echo "Agent Users:\n";
foreach ($users as $u) {
    echo "  User ID={$u->id}, code={$u->code}, name={$u->full_name}\n";
}

echo "\nAgents records:\n";
foreach ($agents as $a) {
    $name = is_array($a->name) ? json_encode($a->name) : $a->name;
    echo "  Agent ID={$a->id}, code={$a->code}, name={$name}\n";
}

echo "\nCounts: " . $users->count() . " agent users, " . $agents->count() . " agents\n";
