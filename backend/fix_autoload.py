import re

filepath = r'vendor\composer\autoload_psr4.php'
with open(filepath, 'r', encoding='utf-8') as f:
    content = f.read()

# The PHP line to add - single backslash in PHP string means escaped backslash
new_line = "    'PhpMqtt\\\\Client\\\\' => array($vendorDir . '/php-mqtt/client/src'),\n"

# Insert before closing );
content = content.rstrip()
if content.endswith(');'):
    content = content[:-2] + new_line + ');'
else:
    print('ERROR: could not find closing );')
    print(repr(content[-50:]))
    exit(1)

with open(filepath, 'w', encoding='utf-8', newline='\n') as f:
    f.write(content)

print('Written successfully')

with open(filepath, 'r', encoding='utf-8') as f:
    for line in f:
        if 'PhpMqtt' in line:
            print('Found:', repr(line))
