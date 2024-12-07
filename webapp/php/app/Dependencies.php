<?php

namespace App;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
use DI\ContainerBuilder;
use Nyholm\Psr7\Factory\Psr17Factory;
use PDO;
use Slim\Factory\AppFactory;

require 'vendor/autoload.php';

const POSTS_PER_PAGE = 20;
const UPLOAD_LIMIT = 10 * 1024 * 1024;

class Dependencies
{
    public static function initializeContainer(): \DI\Container
    {
        $psr17Factory = new Psr17Factory();
        $containerBuilder = new ContainerBuilder();
        $containerBuilder->addDefinitions([
            ResponseFactoryInterface::class => $psr17Factory,
            'settings' => function () {
                return [
                    'public_folder' => dirname(__DIR__) . '/public',
                    'db' => [
                        'host' => $_SERVER['ISUCONP_DB_HOST'] ?? 'localhost',
                        'port' => $_SERVER['ISUCONP_DB_PORT'] ?? 3306,
                        'username' => $_SERVER['ISUCONP_DB_USER'] ?? 'isuconp',
                        'password' => $_SERVER['ISUCONP_DB_PASSWORD'] ?? 'isuconp',
                        'database' => $_SERVER['ISUCONP_DB_NAME'] ?? 'isuconp',
                    ],
                ];
            },
            'db' => function ($c) {
                $config = $c->get('settings')['db'];
                return new PDO(
                    "mysql:dbname={$config['database']};host={$config['host']};port={$config['port']};charset=utf8mb4",
                    $config['username'],
                    $config['password'],
                    [PDO::ATTR_PERSISTENT => true]
                );
            },
        ]);

        $container = $containerBuilder->build();

        return $container;
    }

    public static function setupRoutes($container): \DI\Container
    {
        $container->set('view', function ($c) {
            return new class(dirname(__DIR__) . '/views/') extends \Slim\Views\PhpRenderer {
                public function render(\Psr\Http\Message\ResponseInterface $response, string $template, array $data = []): ResponseInterface
                {
                    $data += ['view' => $template];
                    return parent::render($response, 'layout.php', $data);
                }
            };
        });

        $container->set('flash', function () {
            return new \Slim\Flash\Messages;
        });

        $container->set('helper', function ($c) {
            return new class($c) {
                public PDO $db;

                public function __construct($c)
                {
                    $this->db = $c->get('db');
                }

                public function db()
                {
                    return $this->db;
                }

                public function db_initialize()
                {
                    $db = $this->db();
                    $sql = [];
                    $sql[] = 'DELETE FROM users WHERE id > 1000';
                    $sql[] = 'DELETE FROM posts WHERE id > 10000';
                    $sql[] = 'DELETE FROM comments WHERE id > 100000';
                    $sql[] = 'UPDATE users SET del_flg = 0';
                    $sql[] = 'UPDATE users SET del_flg = 1 WHERE id % 50 = 0';
                    foreach ($sql as $s) {
                        $db->query($s);
                    }
                }

                public function fetch_first($query, ...$params)
                {
                    $db = $this->db();
                    $ps = $db->prepare($query);
                    $ps->execute($params);
                    $result = $ps->fetch();
                    $ps->closeCursor();
                    return $result;
                }

                public function try_login($account_name, $password)
                {
                    $user = $this->fetch_first('SELECT * FROM users WHERE account_name = ? AND del_flg = 0', $account_name);
                    if ($user !== false && calculate_passhash($user['account_name'], $password) == $user['passhash']) {
                        return $user;
                    } elseif ($user) {
                        return null;
                    } else {
                        return null;
                    }
                }

                public function get_session_user()
                {
                    if (isset($_SESSION['user'], $_SESSION['user']['id'])) {
                        return $this->fetch_first('SELECT * FROM `users` WHERE `id` = ?', $_SESSION['user']['id']);
                    } else {
                        return null;
                    }
                }

                public function make_posts(array $results, $options = [])
                {
                    $options += ['all_comments' => false];
                    $all_comments = $options['all_comments'];

                    $comments = [];
                    $post_ids = array_column($results, 'id');
                    if (count($post_ids) > 0) {
                        $placeholders = implode(',', array_fill(0, count($post_ids), '?'));
                        $query = "SELECT * FROM `comments` WHERE `post_id` IN ($placeholders) ORDER BY `created_at` DESC";
                        $stmt = $this->db()->prepare($query);
                        $stmt->execute($post_ids);
                        $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    }

                    $grouped_comments = [];
                    foreach ($comments as $comment) {
                        $grouped_comments[$comment['post_id']][] = $comment;
                    }

                    $user_ids = array_column($comments, 'user_id');
                    if (count($user_ids) > 0) {
                        $user_id_placeholders = implode(',', array_fill(0, count($user_ids), '?'));
                        $user_query = "SELECT * FROM `users` WHERE `id` IN ($user_id_placeholders)";
                        $user_stmt = $this->db()->prepare($user_query);
                        $user_stmt->execute($user_ids);
                        $users = $user_stmt->fetchAll(PDO::FETCH_ASSOC);

                        $user_map = [];
                        foreach ($users as $user) {
                            $user_map[$user['id']] = $user;
                        }
                    }

                    $posts = [];
                    foreach ($results as $post) {
                        $post['comment_count'] = count($grouped_comments[$post['id']] ?? []);

                        $post['comments'] = $grouped_comments[$post['id']] ?? [];
                        if (!$all_comments) {
                            $post['comments'] = array_slice($post['comments'], 0, 3);
                        }

                        $comments = $post['comments'];
                        foreach ($comments as &$comment) {
                            $comment['user'] = $user_map[$comment['user_id']] ?? null;
                        }
                        unset($comment);
                        $post['comments'] = array_reverse($comments);

                        $post['user'] = ['account_name' => $post['account_name']];
                        $posts[] = $post;
                    }
                    return $posts;
                }
            };
        });

        AppFactory::setContainer($container);
        $app = AppFactory::create();

        $app->get('/initialize', function (Request $request, Response $response) use ($container) {
            $container->get('helper')->db_initialize();
            return $response;
        });

        $app->get('/login', function (Request $request, Response $response) use ($container) {
            if ($container->get('helper')->get_session_user() !== null) {
                return redirect($response, '/', 302);
            }
            return $container->get('view')->render($response, 'login.php', [
                'me' => null,
                'flash' => $container->get('flash')->getFirstMessage('notice'),
            ]);
        });

        $app->post('/login', function (Request $request, Response $response) use ($container) {
            if ($container->get('helper')->get_session_user() !== null) {
                return redirect($response, '/', 302);
            }

            $db = $container->get('db');
            $params = $request->getParsedBody();
            $user = $container->get('helper')->try_login($params['account_name'], $params['password']);

            if ($user) {
                $_SESSION['user'] = [
                    'id' => $user['id'],
                ];
                return redirect($response, '/', 302);
            } else {
                $container->get('flash')->addMessage('notice', 'アカウント名かパスワードが間違っています');
                return redirect($response, '/login', 302);
            }
        });

        $app->get('/register', function (Request $request, Response $response) use ($container) {
            if ($container->get('helper')->get_session_user() !== null) {
                return redirect($response, '/', 302);
            }
            return $container->get('view')->render($response, 'register.php', [
                'me' => null,
                'flash' => $container->get('flash')->getFirstMessage('notice'),
            ]);
        });


        $app->post('/register', function (Request $request, Response $response) use ($container) {
            if ($container->get('helper')->get_session_user()) {
                return redirect($response, '/', 302);
            }

            $params = $request->getParsedBody();
            $account_name = $params['account_name'];
            $password = $params['password'];

            $validated = validate_user($account_name, $password);
            if (!$validated) {
                $container->get('flash')->addMessage('notice', 'アカウント名は3文字以上、パスワードは6文字以上である必要があります');
                return redirect($response, '/register', 302);
            }

            $user = $container->get('helper')->fetch_first('SELECT 1 FROM users WHERE `account_name` = ?', $account_name);
            if ($user) {
                $container->get('flash')->addMessage('notice', 'アカウント名がすでに使われています');
                return redirect($response, '/register', 302);
            }

            $db = $container->get('db');
            $ps = $db->prepare('INSERT INTO `users` (`account_name`, `passhash`) VALUES (?,?)');
            $ps->execute([
                $account_name,
                calculate_passhash($account_name, $password)
            ]);
            $_SESSION['user'] = [
                'id' => $db->lastInsertId(),
            ];
            return redirect($response, '/', 302);
        });

        $app->get('/logout', function (Request $request, Response $response) {
            unset($_SESSION['user']);
            return redirect($response, '/', 302);
        });

        $app->get('/', function (Request $request, Response $response) use ($container) {
            $me = $container->get('helper')->get_session_user();

            $db = $container->get('db');
            $ps = $db->prepare('SELECT `posts`.`id`, `posts`.`user_id`, `posts`.`body`, `posts`.`mime`, `posts`.`created_at`, `users`.`account_name` FROM `posts` FORCE INDEX(`created_at_idx`) JOIN `users` ON `posts`.`user_id` = `users`.`id` WHERE `users`.`del_flg` = 0 ORDER BY `created_at` DESC LIMIT 20');
            $ps->execute();
            $results = $ps->fetchAll(PDO::FETCH_ASSOC);
            $posts = $container->get('helper')->make_posts($results);

            return $container->get('view')->render($response, 'index.php', [
                'posts' => $posts,
                'me' => $me,
                'flash' => $container->get('flash')->getFirstMessage('notice'),
            ]);
        });

        $app->get('/posts', function (Request $request, Response $response) use ($container) {
            $params = $request->getQueryParams();
            $max_created_at = $params['max_created_at'] ?? null;
            $db = $container->get('db');
            $ps = $db->prepare('SELECT `posts`.`id`, `posts`.`user_id`, `posts`.`body`, `posts`.`mime`, `posts`.`created_at`, `users`.`account_name` FROM `posts` FORCE INDEX(`created_at_idx`) JOIN `users` ON `posts`.`user_id` = `users`.`id` WHERE `users`.`del_flg` = 0 AND `posts`.`created_at` <= ? ORDER BY `posts`.`created_at` DESC LIMIT 20');
            $ps->execute([$max_created_at === null ? null : $max_created_at]);
            $results = $ps->fetchAll(PDO::FETCH_ASSOC);
            $posts = $container->get('helper')->make_posts($results);

            return $container->get('view')->render($response, 'posts.php', ['posts' => $posts]);
        });

        $app->get('/posts/{id}', function (Request $request, Response $response, $args) use ($container) {
            $db = $container->get('db');
            $ps = $db->prepare('SELECT `posts`.`id`, `posts`.`user_id`, `posts`.`body`, `posts`.`mime`, `posts`.`created_at`, `users`.`account_name` FROM `posts` JOIN `users` ON `posts`.`user_id` = `users`.`id` WHERE `posts`.`id` = ?');
            $ps->execute([$args['id']]);
            $results = $ps->fetchAll(PDO::FETCH_ASSOC);
            $posts = $container->get('helper')->make_posts($results, ['all_comments' => true]);

            if (count($posts) == 0) {
                $response->getBody()->write('404');
                return $response->withStatus(404);
            }

            $post = $posts[0];

            $me = $container->get('helper')->get_session_user();

            return $container->get('view')->render($response, 'post.php', ['post' => $post, 'me' => $me]);
        });

        $app->post('/', function (Request $request, Response $response) use ($container) {
            $me = $container->get('helper')->get_session_user();

            if ($me === null) {
                return redirect($response, '/login', 302);
            }

            $params = $request->getParsedBody();
            if ($params['csrf_token'] !== session_id()) {
                $response->getBody()->write('422');
                return $response->withStatus(422);
            }

            if ($_FILES['file']) {
                $mime = '';
                // 投稿のContent-Typeからファイルのタイプを決定する
                if (strpos($_FILES['file']['type'], 'jpeg') !== false) {
                    $mime = 'image/jpeg';
                } elseif (strpos($_FILES['file']['type'], 'png') !== false) {
                    $mime = 'image/png';
                } elseif (strpos($_FILES['file']['type'], 'gif') !== false) {
                    $mime = 'image/gif';
                } else {
                    $container->get('flash')->addMessage('notice', '投稿できる画像形式はjpgとpngとgifだけです');
                    return redirect($response, '/', 302);
                }

                if (strlen(file_get_contents($_FILES['file']['tmp_name'])) > UPLOAD_LIMIT) {
                    $container->get('flash')->addMessage('notice', 'ファイルサイズが大きすぎます');
                    return redirect($response, '/', 302);
                }

                $db = $container->get('db');
                $query = 'INSERT INTO `posts` (`user_id`, `mime`, `imgdata`, `body`) VALUES (?,?,?,?)';
                $ps = $db->prepare($query);
                $ps->execute([
                    $me['id'],
                    $mime,
                    file_get_contents($_FILES['file']['tmp_name']),
                    $params['body'],
                ]);
                $pid = $db->lastInsertId();
                return redirect($response, "/posts/{$pid}", 302);
            } else {
                $container->get('flash')->addMessage('notice', '画像が必須です');
                return redirect($response, '/', 302);
            }
        });

        $app->get('/image/{id}.{ext}', function (Request $request, Response $response, $args) use ($container) {
            if ($args['id'] == 0) {
                return $response;
            }

            $post = $container->get('helper')->fetch_first('SELECT * FROM `posts` WHERE `id` = ?', $args['id']);

            if (($args['ext'] == 'jpg' && $post['mime'] == 'image/jpeg') ||
                ($args['ext'] == 'png' && $post['mime'] == 'image/png') ||
                ($args['ext'] == 'gif' && $post['mime'] == 'image/gif')
            ) {
                $response->getBody()->write($post['imgdata']);
                file_put_contents("../public/image/" . $args['id'] . "." . $args['ext'], $post['imgdata']);
                return $response->withHeader('Content-Type', $post['mime']);
            }
            $response->getBody()->write('404');
            return $response->withStatus(404);
        });

        $app->post('/comment', function (Request $request, Response $response) use ($container) {
            $me = $container->get('helper')->get_session_user();

            if ($me === null) {
                return redirect($response, '/login', 302);
            }

            $params = $request->getParsedBody();
            if ($params['csrf_token'] !== session_id()) {
                $response->getBody()->write('422');
                return $response->withStatus(422);
            }

            // TODO: /\A[0-9]\Z/ か確認
            if (preg_match('/[0-9]+/', $params['post_id']) == 0) {
                $response->getBody()->write('post_idは整数のみです');
                return $response;
            }
            $post_id = $params['post_id'];

            $query = 'INSERT INTO `comments` (`post_id`, `user_id`, `comment`) VALUES (?,?,?)';
            $ps = $container->get('db')->prepare($query);
            $ps->execute([
                $post_id,
                $me['id'],
                $params['comment']
            ]);

            return redirect($response, "/posts/{$post_id}", 302);
        });

        $app->get('/admin/banned', function (Request $request, Response $response) use ($container) {
            $me = $container->get('helper')->get_session_user();

            if ($me === null) {
                return redirect($response, '/login', 302);
            }

            if ($me['authority'] == 0) {
                $response->getBody()->write('403');
                return $response->withStatus(403);
            }

            $db = $container->get('db');
            $ps = $db->prepare('SELECT * FROM `users` WHERE `authority` = 0 AND `del_flg` = 0 ORDER BY `created_at` DESC');
            $ps->execute();
            $users = $ps->fetchAll(PDO::FETCH_ASSOC);

            return $container->get('view')->render($response, 'banned.php', ['users' => $users, 'me' => $me]);
        });

        $app->post('/admin/banned', function (Request $request, Response $response) use ($container) {
            $me = $container->get('helper')->get_session_user();

            if ($me === null) {
                return redirect($response, '/login', 302);
            }

            if ($me['authority'] == 0) {
                $response->getBody()->write('403');
                return $response->withStatus(403);
            }

            $params = $request->getParsedBody();
            if ($params['csrf_token'] !== session_id()) {
                $response->getBody()->write('422');
                return $response->withStatus(422);
            }

            $db = $container->get('db');
            $query = 'UPDATE `users` SET `del_flg` = ? WHERE `id` = ?';
            foreach ($params['uid'] as $id) {
                $ps = $db->prepare($query);
                $ps->execute([1, $id]);
            }

            return redirect($response, '/admin/banned', 302);
        });

        $app->get('/@{account_name}', function (Request $request, Response $response, $args) use ($container) {
            $db = $container->get('db');
            $user = $container->get('helper')->fetch_first('SELECT * FROM `users` WHERE `account_name` = ? AND `del_flg` = 0', $args['account_name']);

            if ($user === false) {
                $response->getBody()->write('404');
                return $response->withStatus(404);
            }

            $ps = $db->prepare('SELECT `posts`.`id`, `posts`.`user_id`, `posts`.`body`, `posts`.`mime`, `posts`.`created_at`, `users`.`account_name` FROM `posts` FORCE INDEX(`user_id_created_at_idx`) JOIN `users` ON `posts`.`user_id` = `users`.`id` WHERE `users`.`del_flg` = 0 AND `posts`.`user_id` = ? ORDER BY `posts`.`created_at` DESC LIMIT 20');
            $ps->execute([$user['id']]);
            $results = $ps->fetchAll(PDO::FETCH_ASSOC);
            $posts = $container->get('helper')->make_posts($results);

            $comment_count = $container->get('helper')->fetch_first('SELECT COUNT(*) AS count FROM `comments` WHERE `user_id` = ?', $user['id'])['count'];

            $ps = $db->prepare('SELECT `id` FROM `posts` WHERE `user_id` = ?');
            $ps->execute([$user['id']]);
            $post_ids = array_column($ps->fetchAll(PDO::FETCH_ASSOC), 'id');
            $post_count = count($post_ids);

            $commented_count = 0;
            if ($post_count > 0) {
                $placeholder = implode(',', array_fill(0, count($post_ids), '?'));
                $commented_count = $container->get('helper')->fetch_first("SELECT COUNT(*) AS count FROM `comments` WHERE `post_id` IN ({$placeholder})", ...$post_ids)['count'];
            }

            $me = $container->get('helper')->get_session_user();

            return $container->get('view')->render($response, 'user.php', ['posts' => $posts, 'user' => $user, 'post_count' => $post_count, 'comment_count' => $comment_count, 'commented_count' => $commented_count, 'me' => $me]);
        });

        $container->set('app', $app);
        return $container;
    }
}
