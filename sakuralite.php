<?php
// SakuraLite: Un pequeño BBS creado por LexiTsunami (Versión 1.2)
// Mantente al día en https://github.com/LexiTsunami/SakuraLite

// Estética
$title = ''; // El título de tu BBS. Aparecerá en la parte superior de la página y en la pestaña del navegador. Déjalo en blanco para usar el título predeterminado.
$subtitle = ''; // El subtítulo de tu BBS. Aparecerá debajo del título si no está vacío.
$background = '#FFFFEE'; // Código de color (hexadecimal o nombre) para el fondo del BBS
$textcolor = '#800000'; // Código de color para todo el texto del BBS
$linkcolor = '#0000EE'; // Código de color para todos los enlaces del BBS
$linkhover = '#0000EE'; // Código de color para enlaces visitados
$fonts = 'arial, helvetica, sans-serif'; // Familia de fuentes del BBS, en orden de preferencia, sin comillas y separadas por comas
$formsidecolor = '#ea8'; // Color de los campos "Nombre", "Correo" y "Comentario" en el formulario
$postbackground = '#F0E0D6'; // Color de fondo de cada publicación
$posternamecolor = '#117743'; // Color del nombre del autor
$errortextcolor = 'red'; // Color del texto de error
$defaultname = 'Anónimo'; // Nombre predeterminado si el usuario deja el campo vacío
$tripsymbol = '!'; // Símbolo que representa un tripcode (ej. Nombre!Trip)
$tripfake = '?'; // Símbolo que reemplaza al trip si se intenta falsificar
$deletionphrase = 'Eliminado'; // Texto que aparece en lugar del contenido de una publicación eliminada
$bulletpoints = [ // Lista de puntos que aparecen bajo el formulario. Usa [] para desactivar.
    'Este es un punto de ejemplo.',
    '¡Cámbialos, elimínalos o añade más!'
];

// Comportamiento
$managepassword = 'CAMBIAME'; // Contraseña para acceder al panel de administración
$managecookie = 'sakuralite_manage'; // Cookie que almacena la contraseña. Puedes poner algo aleatorio como medida de seguridad
$lockfile = 'sakuralite.lock'; // Archivo que se crea cuando se desactivan las publicaciones
$sakuralitefile = 'sakuralite.php'; // Nombre de este archivo
$datafile = 'cambiamenombre.dat'; // Archivo donde se almacenan las publicaciones. Usa un nombre aleatorio
$bansfile = 'cambiamenombre2.dat'; // Archivo donde se almacenan los baneos (hashes). Usa un nombre aleatorio
$postsperpage = 15; // Cantidad de publicaciones por página (recomendado: 15–20)
$maxpages = 15; // Número máximo de páginas. Las publicaciones que caigan fuera se eliminan permanentemente
$forcedanonymity = false; // false = permite nombre y correo; true = fuerza nombre predeterminado y elimina campo de correo
$cooldown = 5; // Tiempo de espera (en segundos) entre publicaciones del mismo usuario
$namelimit = 20; // Límite de caracteres para el nombre
$commentlimit = 200; // Límite de caracteres para el comentario
$badstrings = [ // Frases prohibidas (sin distinción de mayúsculas). Usa [] para desactivar.
    'esto es malo',
    'los bbs apestan'
];

// No edites nada debajo de esta línea a menos que sepas lo que haces

ini_set('default_charset', 'UTF-8');

if (version_compare(PHP_VERSION, '7.4.0', '<')) {
    die('<a href="//github.com/LexiTsunami/SakuraLite">SakuraLite</a> requiere PHP 7.4 o superior. Actualmente usas PHP ' . PHP_VERSION);
}

$ip = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['REMOTE_ADDR'];
$hashedip = substr(sha1($ip), 0, 16);

if (!file_exists($bansfile)) {
    file_put_contents($bansfile, '');
}

$bannedips = file($bansfile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
if (in_array($hashedip, $bannedips)) {
    bbserror('Estás baneado de este BBS.');
}

function readposts(): array {
    global $datafile;
    if (!file_exists($datafile)) return [];
    $lines = file($datafile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $posts = [];
    foreach ($lines as $line) {
        $parts = explode('|', $line, 8);
        if (count($parts) < 8) continue;
        [$encname, $encomment, $time, $num, $now, $postiphash, $email, $deleted] = $parts;
        $posts[] = [
            'name' => base64_decode($encname),
            'comment' => base64_decode($encomment),
            'time' => $time,
            'num' => $num,
            'now' => (int)$now,
            'postiphash' => $postiphash,
            'email' => $email,
            'deleted' => $deleted
        ];
    }
    return $posts;
}

function saveposts(array $posts): void {
    global $datafile;
    $lines = [];
    foreach ($posts as $p) {
        $lines[] = base64_encode($p['name']).'|'.base64_encode($p['comment']).'|'.$p['time'].'|'.$p['num'].'|'.$p['now'].'|'.$p['postiphash'].'|'.$p['email'].'|'.$p['deleted'];
    }
    file_put_contents($datafile, implode(PHP_EOL,$lines).PHP_EOL, LOCK_EX);
}

function nextnum(): int {
    global $datafile;
    if (!file_exists($datafile)) return 1;

    $lines = file($datafile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (!$lines) return 1;

    $lastline = end($lines);
    if (!$lastline) return 1;

    $parts = explode('|', $lastline, 8);
    return isset($parts[3]) ? ((int)$parts[3] + 1) : 1;
}

function bbserror(string $message): void {
    global $title, $subtitle, $background, $textcolor, $linkcolor, $linkhover, $fonts, $errortextcolor;
    $pagetitle = $title ?: 'SakuraLite BBS';
    $pagesubtitle = $subtitle ? '<h3>'.$subtitle.'</h3>' : '';
    echo <<<HTML
<title>$pagetitle</title>
<meta charset="UTF-8">
<body bgcolor="$background" text="$textcolor" link="$linkcolor" vlink="$linkhover" style="font-family:$fonts;">
<center><h1>$pagetitle</h1>$pagesubtitle</center>
<hr>
<center><span style="color:$errortextcolor;font-weight:bold;">$message</span><br>[<a href="index.html">Volver</a>]</center>
<hr>
<br clear="all">
<center>- <a href="//github.com/LexiTsunami/SakuraLite">SakuraLite</a> -</center>
HTML;
    exit;
}

function buildpages(array $posts): void {
    global $postsperpage, $maxpages;

    $posts = array_slice($posts, -$postsperpage * $maxpages);
    $totalpages = (int) ceil(count($posts) / $postsperpage);
    if ($totalpages === 0) $totalpages = 1;

    $pages = array_chunk(array_reverse($posts), $postsperpage);

    foreach ($pages as $i => $pageposts) {
        $html = genpage($pageposts, $i, $totalpages);
        $filename = $i === 0 ? 'index.html' : $i.'.html';
        file_put_contents($filename, $html, LOCK_EX);
    }

    saveposts($posts);
}

function genpage(array $posts, int $pagenumber, int $totalpages): string {
    global $title, $subtitle, $background, $textcolor, $linkcolor, $linkhover, $fonts, $formsidecolor, $sakuralitefile, $defaultname, $forcedanonymity, $deletionphrase, $posternamecolor, $bulletpoints, $postbackground;

    $pagetitle = $title ?: 'SakuraLite BBS';
    $pagesubtitle = $subtitle ? '<h3>'.$subtitle.'</h3>' : '';

    $posthtml = '';
    foreach ($posts as $post) {
      if($post['deleted'] > 0) { $comment = "<i>".$deletionphrase."</i>"; } else { $comment = nl2br(htmlspecialchars($post['comment'])); }
      $displayname = htmlspecialchars($post['name']);
      if($post['deleted'] > 0) { $displayname = $defaultname; }
      if($post['deleted'] == 0) {
        if ($post['email'] !== '' && filter_var(base64_decode($post['email']), FILTER_VALIDATE_EMAIL)) {
            $displayname = '<a href="mailto:'.htmlspecialchars(base64_decode($post['email'])).'">'.$displayname.'</a>';
        }
      }
        $posthtml .= '<div style="padding:8px;padding-top:5px;margin-bottom:5px;background-color:'.$postbackground.';">
            <span style="color:'.$posternamecolor.';"><b>'.$displayname.'</b></span>
            <span>'.$post['time'].'</span>
            <span style="float:right;">#'.$post['num'].'</span>
            <div>'.$comment.'</div>
        </div>';
    }

    $pagehtml = '<table align="left" border="1"><tbody><tr>';
    $prevpage = $pagenumber > 0 ? ($pagenumber === 1 ? 'index.html' : ($pagenumber-1).'.html') : '#';
    $pagehtml .= $pagenumber > 0 ? '<td><a href="'.$prevpage.'"><button>Anterior</button></a></td><td>' : '<td>Anterior</td><td>';

    for ($i = 0; $i < $totalpages; $i++) {
        $href = $i === 0 ? 'index.html' : $i.'.html';
        $pagehtml .= $i === $pagenumber ? '[<b>'.$i.'</b>]&nbsp;' : '[<a href="'.$href.'">'.$i.'</a>]&nbsp;';
    }

    if ($pagenumber < $totalpages-1) {
        $nextpage = ($pagenumber+1).'.html';
        $pagehtml .= '</td><td><a href="'.$nextpage.'"><button>Siguiente</button></a></td></tr></tbody></table>';
    } else {
        $pagehtml .= '</td><td>Siguiente</td></tr></tbody></table>';
    }

    if($forcedanonymity == true) {
        $namefield = '<input type="text" name="name" size="28" value="'.$defaultname.'" disabled="disabled"> <input type="submit">';
        $emailsection = '';
    } else {
        $namefield = '<input type="text" name="name" size="28">';
        $emailsection = '<tr><td style="background-color:'.$formsidecolor.';"><b>Correo</b></td><td><input type="email" name="email" size="28"> <input type="submit"></td></tr>';
    }
    
if (!empty($bulletpoints)) {
    $bullets = '<table style="width: 100%; max-width: 304px;"><tbody><tr><td><ul style="
        padding-left: 15px;
        font-size: 10pt;
        margin-bottom: 0px;
    ">';
    foreach ($bulletpoints as $item) {
        if (is_array($item)) {
            $item = json_encode($item);
        }
        $bullets .= '<li>' . htmlspecialchars((string)$item, ENT_QUOTES | ENT_HTML5) . '</li>';
    }
    $bullets .= '</ul></td></tr></tbody></table>';
}

    return <<<HTML
<title>$pagetitle</title>
<meta charset="UTF-8">
<body bgcolor="$background" text="$textcolor" link="$linkcolor" vlink="$linkhover" style="font-family:$fonts;">
<center><h1>$pagetitle</h1>$pagesubtitle<form method="POST" action="$sakuralitefile" style="margin-bottom:0px;">
<table><tbody>
<tr><td style="background-color:$formsidecolor;"><b>Nombre</b></td><td>$namefield</td></tr>
$emailsection
<tr><td style="background-color:$formsidecolor;"><b>Comentario</b></td><td><textarea name="com" cols="48" rows="4"></textarea></td></tr>
</tbody></table>
$bullets
</form></center>
<hr>
$posthtml
<hr>
$pagehtml
<div style="float: right;">[<a href="$sakuralitefile?mode=manage">Administrar</a>]</div>
<br clear="all">
<center>- <a href="//github.com/LexiTsunami/SakuraLite">SakuraLite</a> -</center>
HTML;
}

$mode = $_GET['mode'] ?? '';
$canmanage = false;

if ($mode === 'manage') {
    $hashedcookie = $_COOKIE[$managecookie] ?? '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['managepassword'])) {
        if (sha1($_POST['managepassword']) === sha1($managepassword)) {
            setcookie($managecookie, sha1($managepassword), 0);
            $canmanage = true;
        } else {
            bbserror('Contraseña incorrecta.');
        }
    } elseif ($hashedcookie === sha1($managepassword)) {
        $canmanage = true;
    }

    if (!$canmanage) {
    $pagetitle = $title ?: 'SakuraLite BBS';
    $pagesubtitle = $subtitle ? '<h3>'.$subtitle.'</h3>' : '';
        echo <<<HTML
<title>$pagetitle</title>
<meta charset="UTF-8">
<body bgcolor="$background" text="$textcolor" link="$linkcolor" vlink="$linkhover" style="font-family:$fonts;">
<center><h1>$pagetitle</h1>$pagesubtitle</center>
<hr>
<center><form method="POST" action="?mode=manage">
Contraseña: <input type="password" name="managepassword"> <input type="submit" value="Iniciar sesión">
</form></center>
<hr>
<center>- <a href="//github.com/LexiTsunami/SakuraLite">SakuraLite</a> -</center>
HTML;
        exit;
    }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $posts = readposts();
    $bans = file($bansfile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $page = (int)($_POST['page'] ?? 0);

    $markasdeleted = function (&$posts, $num) {
        foreach ($posts as &$p) {
            if ((int)$p['num'] === $num) {
                $p['deleted'] = 1;
                break;
            }
        }
    };

    if (isset($_POST['delete'])) {
        $num = (int)$_POST['delete'];
        $markasdeleted($posts, $num);
    }

    if (isset($_POST['delete_ban'])) {
        [$numberstring, $hash] = explode('|', $_POST['delete_ban'], 2);
        $num = (int)$numberstring;
        $hash = (string)$hash;
        if ($hash !== '' && !in_array($hash, $bans, true)) {
            file_put_contents($bansfile, $hash . PHP_EOL, FILE_APPEND | LOCK_EX);
        }
        $markasdeleted($posts, $num);
    }

    if (isset($_POST['ban'])) {
        $hash = (string)$_POST['ban'];
        if ($hash !== '' && !in_array($hash, $bans, true)) {
            file_put_contents($bansfile, $hash . PHP_EOL, FILE_APPEND | LOCK_EX);
        }
    }

    saveposts(array_values($posts));
    buildpages($posts);
    header('Location: '.$sakuralitefile.'?mode=manage&page=' . $page);
    exit;
}

    if (isset($_GET['togglelock'])) {
        if (file_exists($lockfile)) unlink($lockfile);
        else file_put_contents($lockfile, 'Este BBS usa SakuraLite de LexiTsunami (https://github.com/LexiTsunami/SakuraLite) y está bloqueado.');
        header('Location: '.$sakuralitefile.'?mode=manage');
        exit;
    }

    $posts = array_reverse(readposts());
    $allposts = count($posts);
    $totalpages = (int) ceil($allposts / $postsperpage);
    $pagenumber = (int)($_GET['page'] ?? 0);
    if ($pagenumber < 0) $pagenumber = 0;
    if ($pagenumber >= $totalpages) $pagenumber = $totalpages - 1;
    $pageposts = array_slice($posts, $pagenumber*$postsperpage, $postsperpage);

    $lockbutton = file_exists($lockfile)
        ? '<a href="?mode=manage&togglelock"><button>Desbloquear publicaciones</button></a>'
        : '<a href="?mode=manage&togglelock"><button>Bloquear publicaciones</button></a>';

    $pagetitle = $title ?: 'SakuraLite BBS';
    $pagesubtitle = $subtitle ? '<h3>'.$subtitle.'</h3>' : '';
    
    echo <<<HTML
<title>$pagetitle</title>
<meta charset="UTF-8">
<body bgcolor="$background" text="$textcolor" link="$linkcolor" vlink="$linkhover" style="font-family:$fonts;">
<center><h1>$pagetitle</h1>$pagesubtitle</center>
<hr>
<center>$lockbutton</center>
<br>
<form method="POST" action="?mode=manage">
<input type="hidden" name="page" value="$pagenumber">
<table border="0" cellpadding="5" style="margin:auto;">
<tr bgcolor=6080f6><th>#</th><th>Nombre</th><th>Correo</th><th>Comentario</th><th>Hora</th><th>ID del usuario</th><th>Acciones</th></tr>
HTML;

foreach ($pageposts as $idx => $p) {
    $num = $p['num'];
    $name = htmlspecialchars($p['name']);
    $email = htmlspecialchars(base64_decode($p['email']));
    if(empty($email)) { $email = "N/D"; } else { $email = "<a href='mailto:".$email."'>".$email."</a>"; }
    $comment = htmlspecialchars($p['comment']);
    $time = $p['time'];
    $posterhash = $p['postiphash'];
    $bg = ($idx % 2) ? "d6d6f6" : "f6f6f6";
    echo "<tr bgcolor='$bg'>
            <td>$num</td>
            <td>$name</td>
            <td>$email</td>
            <td>$comment</td>
            <td>$time</td>
            <td>$posterhash</td>
            <td>
                <input type='hidden' name='page' value='$pagenumber'>";
  if($p['deleted'] > 0) { echo "<i>Publicación eliminada</i><br>"; } else { echo "<button type='submit' name='delete' value='".$num."'>Eliminar</button><br>"; }
  echo "<button type='submit' name='ban' value='$posterhash'>Banear IP</button><br>";
  if($p['deleted'] == 0) { echo "<button type='submit' name='delete_ban' value='".$num."|".$posterhash."'>Eliminar y banear IP</button>"; }
echo "
            </td>
        </tr>";
    }

    echo "</table>";
echo '</form><table align="center" border="1"><tbody><tr>';

if ($pagenumber > 0) {
    $prevpage = $pagenumber === 1 ? '?mode=manage&page=0' : '?mode=manage&page='.($pagenumber-1);
    echo '<td><a href="'.$prevpage.'"><button>Anterior</button></a></td><td>';
} else {
    echo '<td>Anterior</td><td>';
}

for ($i = 0; $i < $totalpages; $i++) {
    $href = '?mode=manage&page='.$i;
    if ($i === $pagenumber) {
        echo '[<b>'.$i.'</b>]&nbsp;';
    } else {
        echo '[<a href="'.$href.'">'.$i.'</a>]&nbsp;';
    }
}

if ($pagenumber < $totalpages-1) {
    $nextpage = '?mode=manage&page='.($pagenumber+1);
    echo '</td><td><a href="'.$nextpage.'"><button>Siguiente</button></a></td>';
} else {
    echo '</td><td>Siguiente</td>';
}

echo '</tr></tbody></table>';

    echo "</div></form><hr><div style='float: right;'>[<a href='index.html'>Volver</a>]</div>
<br clear='all'><center>- <a href='//github.com/LexiTsunami/SakuraLite'>SakuraLite</a> -</center>";
    exit;
}

if (file_exists($lockfile)) {
    $hashedcookie = $_COOKIE[$managecookie] ?? '';
    if ($hashedcookie !== sha1($managepassword)) {
        bbserror('Las publicaciones están bloqueadas en este momento.');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $comment = trim($_POST['com'] ?? '');
    
    if (!empty($badstrings)) {
    $pattern = '/' . implode('|', array_map(fn($s) => preg_quote($s, '/'), $badstrings)) . '/i';
    if (preg_match($pattern, $comment) || preg_match($pattern, $name) || preg_match($pattern, $email)) {
        bbserror('Tu publicación contiene una frase prohibida en este BBS.');
    }
}

    if ($comment === '') bbserror('El cuerpo de tu publicación no puede estar vacío.');
    if (strlen($name) > $namelimit) bbserror('Tu nombre no puede superar los ' . $namelimit . ' caracteres.');
    if (strlen($comment) > $commentlimit) bbserror('Tu publicación no puede superar los ' . $commentlimit . ' caracteres.');
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) bbserror('El correo proporcionado no tiene un formato válido.');

    global $tripsymbol, $tripfake, $defaultname;
    $name = preg_replace_callback('/(.)'.$tripsymbol.'(.)/', fn($m) => $m[1].$tripfake.$m[2], $name);

    if (strpos($name, '#') !== false) {
        [$displayname, $trip] = explode('#', $name, 2);
        $trip = substr($trip, 0, 255);
        $salt = strtr(preg_replace('/[^\.\/0-9A-Za-z]/', '.', substr($trip.'H.',1,2)), ':;<=>?@[\\]^_`', 'A-Ga-f');
        $tripcode = $tripsymbol . substr(crypt($trip, $salt), -10);
        $name = $displayname.$tripcode;
    }

    $name = $name ?: $defaultname;
    if($forcedanonymity) $name = $defaultname;
    $comment = substr($comment, 0, 5000);
    $time = date('d/m/y(D)H:i:s');
    $num = nextnum();
    $now = time();

    $posts = array_slice(readposts(), -$postsperpage);
    foreach ($posts as $p) {
        if (($now - $p['now']) < 5) bbserror('Espera 5 segundos antes de publicar de nuevo.');
        if ($p['name'] === $name && $p['comment'] === $comment && $p['postiphash'] === $hashedip) {
            bbserror('Ya has dicho eso recientemente.');
        }
    }

    $entry = base64_encode($name).'|'.base64_encode($comment).'|'.$time.'|'.$num.'|'.$now.'|'.$hashedip.'|'.base64_encode($email).'|0';
    file_put_contents($datafile, $entry.PHP_EOL, FILE_APPEND | LOCK_EX);
    buildpages(readposts());

    header('Location: index.html');
    exit;
}

if (file_exists('index.html')) {
    header('Location: index.html');
    exit;
} else {
    echo genpage([], 0, 1);
}