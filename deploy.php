<?php

$BUNDLE_ROOT = "/home/IT490/deploy/Bundles";
$ENVIRONMENTS = [
    "qa" => [
        "web" => "qa-web",
        "backend"=> "qa-db",
        "dmz"=> "qa-dmz",
    ],
    "prod" => [
        "web"=> "prod-web",
        "backend"=> "prod-db",
        "dmz" => "prod-dmz",
    ],
];

function usage() {
echo "Usage:\n";
echo "  php deploy.php install <web|backend|dmz> <qa|prod>\n";
echo "  php deploy.php mark <web|backend|dmz> <version> passed\n";
 
  exit(1);

}


if ($argc < 3) usage();
$action = $argv[1];
if ($action === "install") {
    if ($argc !== 4) usage();

  
    $target = $argv[2];
    $env = $argv[3];

    global $ENVIRONMENTS, $BUNDLE_ROOT;
    if (!isset($ENVIRONMENTS[$env][$target])) {
        echo "Unknown environment or target.\n";
        exit(1);
    }

$server = $ENVIRONMENTS[$env][$target];

// Get the newest bundle for this target type
$files = glob("$BUNDLE_ROOT/$target/{$target}_*.tar.gz");
if (!$files) {
  echo "No bundles found for $target.\n";
  exit(1);
    }

usort($files, function($a, $b) {
    return filemtime($b) - filemtime($a);
    });
    $latest = $files[0];

    echo "Deploying bundle: $latest\n";

  // Remote deploy path
  if ($target === "web") {
        $remoteInstaller = "/home/ubuntu/deploy/install_web.sh";
    } 
  
  elseif ($target === "backend") {
        $remoteInstaller = "/home/ubuntu/deploy/install_backend.sh";
    } 
  
  elseif ($target === "dmz") {
        $remoteInstaller = "/home/ubuntu/deploy/install_dmz.sh";
    } 
  
  else {
        echo "Unknown install target.\n";
        exit(1);
    }

    // Send bundle + run installer
    $cmd = "scp $latest ubuntu@$server:/home/ubuntu/";
    echo "Running: $cmd\n";
    system($cmd);

    $bundleFile = basename($latest);
    $cmd = "ssh ubuntu@$server '$remoteInstaller /home/ubuntu/$bundleFile'";
    echo "Running: $cmd\n";
    system($cmd);

    echo "Deploy complete.\n";
    exit(0);
}

else if ($action === "mark") {
    if ($argc !== 5) usage();

    $target  = $argv[2];
    $version = $argv[3];
    $status  = $argv[4];

    if ($status !== "passed") {
        echo "Only 'passed' status supported.\n";
        exit(1);
    }

    // Mark bundle as passed in DB
  $cmd = "mysql -udeployuser -pSecure123 deploydb -e "
         . "\"UPDATE bundles SET status='passed' WHERE name='$target' AND version='$version';\"";

      echo "Running: $cmd\n";
    system($cmd);

echo "Bundle marked as passed.\n";
exit(0);
}

else {
    usage();
}
