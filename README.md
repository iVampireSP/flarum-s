# FlarumS

用 Swoole HTTP Server 运行 Flarum，带你飞。


目前还没有做过完整的测试，但是能跑起来。


## 开始使用

### 准备 Swoole

首先，你得有 Swoole。

```bash
php -m | grep swoole
```

如果没有，则可以用以下命令安装。
```bash
pecl install swoole
```

如果你是宝塔面板，可以到面板中安装。

### 安装
```bash
composer require ivampiresp/flarum-s
cp -r vendor/ivampiresp/flarum-s/bin .
chmod +x bin/flarum-s
```

之后，到 bin 目录下编辑 `config.php`，更改监听 IP，端口。
其余的不懂就不要动了。如果你需要更好的性能，可以编辑 `reactor_num` 和 `worker_num`。请根据你的机器配置来。

### 运行

```bash
cd bin
./flarum-s service start
```


## 常见问题

### 使用 FlarumS 安装 Flarum 后，崩溃。

这个问题不是什么大问题，编辑你的站点目录下的 `config.php`，然后将 `url` 改成你站点的实际 URL。
随后再次启动 FlarumS

### 安装扩展后，Flarum 后台没有显示。

因为程序都被加载到内存了，并且不会实时更新。用以下命令重启。

```bash
cd bin
./flarum-s service restart
```

### 更改 Flarum 设置后，不生效，刷新一下又变回去了。

你的 worker 数量可能有很多个，要让所有 worker 生效，应该可以监听到设置更改后自动重载。但是目前没有实现。
你可以手动执行这个命令来重启。

```bash
cd bin
./flarum-s service restart
```