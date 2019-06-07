--
-- Структура таблицы `sites_name`
--

CREATE TABLE `sites_name` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '-' COMMENT 'Имя задачи',
  `active` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'Задача разрешена',
  `run_now` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Задача запущена сейчас'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Дамп данных таблицы `sites_name`
--

INSERT INTO `sites_name` (`id`, `name`, `active`, `run_now`) VALUES
(1, 'RuTracker.org', 1, 0);

-- --------------------------------------------------------

--
-- Структура таблицы `sites_options`
--

CREATE TABLE `sites_options` (
  `id` int(10) UNSIGNED NOT NULL,
  `name_id` int(11) NOT NULL,
  `property` varchar(255) COLLATE utf8_unicode_ci NOT NULL COMMENT 'Свойство',
  `value` varchar(255) COLLATE utf8_unicode_ci NOT NULL COMMENT 'Значение',
  `type` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'string' COMMENT 'Тип переменной'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Дамп данных таблицы `sites_options`
--

INSERT INTO `sites_options` (`id`, `name_id`, `property`, `value`, `type`) VALUES
(1, 1, 'login', 'USER', 'string'),
(2, 1, 'pass', 'PASSWORD', 'string'),
(3, 1, 'host', 'https://rutracker.net/forum/', 'string'),
(4, 1, 'loginform', 'login.php', 'string'),
(5, 1, 'viewtopic', 'viewtopic.php?t=', 'string'),
(6, 1, 'download', 'dl.php?t=', 'string'),
(7, 1, 'lastCheckSite', 'O:8:\"DateTime\":3:{s:4:\"date\";s:26:\"2019-06-07 18:11:34.448754\";s:13:\"timezone_type\";i:3;s:8:\"timezone\";s:18:\"Asia/Yekaterinburg\";}', 'object'),
(13, 1, 'formToken', '', 'string'),
(14, 1, 'code', 'a:0:{}', 'array'),
(15, 1, 'codeStatus', '', 'string'),
(16, 1, 'codeInput', '', 'string');

--
-- Индексы сохранённых таблиц
--

--
-- Индексы таблицы `sites_name`
--
ALTER TABLE `sites_name`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Индексы таблицы `sites_options`
--
ALTER TABLE `sites_options`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `proper` (`name_id`,`property`);

--
-- AUTO_INCREMENT для сохранённых таблиц
--

--
-- AUTO_INCREMENT для таблицы `sites_name`
--
ALTER TABLE `sites_name`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT для таблицы `sites_options`
--
ALTER TABLE `sites_options`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;
COMMIT;
