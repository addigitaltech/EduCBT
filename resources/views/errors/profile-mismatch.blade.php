<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Installation problem</title>
    <style>
        body { font-family: system-ui, sans-serif; background: #f4f6fa; color: #1b2330; display: grid; place-items: center; min-height: 100vh; margin: 0; padding: 1rem; }
        main { max-width: 32rem; background: #fff; border: 1px solid #dce2eb; border-radius: 12px; padding: 1.75rem; line-height: 1.55; }
        code { background: #eef1f6; padding: 0.125rem 0.375rem; border-radius: 4px; }
    </style>
</head>
<body>
<main>
    <h1>This installation is set up incorrectly</h1>
    <p>It was installed as <code>{{ $installedAs }}</code> but is now configured as <code>{{ $configuredAs }}</code>. A deployment cannot change profile. Do not use it, and contact your technical support.</p>
</main>
</body>
</html>
