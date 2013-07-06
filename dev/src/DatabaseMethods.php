<?php

function getMainMenu ($app) {
  $sql = "select title, slug from posts where status = 'published' and main_menu = 1 order by seq";
  $main_menu = $app['db']->fetchAll($sql);

  return $main_menu;
}

function getPost($app, $slug) {
  $sql = "select * from posts where slug = ?";
  $post = $app['db']->fetchAssoc($sql, array($slug));

  if ($post) {
    $post['content'] = markToHtml($post['content']);
  }

  return $post;
}

function getPublishedPost($app, $slug, $type = '') {
  if ($type) {
    $type = "and type ='$type'";
  }

  $sql = "select * from posts where status = 'published' $type and slug = ?";
  $post = $app['db']->fetchAssoc($sql, array($slug));

  if ($post) {
    $post['content'] = markToHtml($post['content']);
  }

  return $post;
}

function getPostNav($app, $publish_date, $current_id) {
  $prev_sql = "select id, title, slug from posts where status = 'published' and ((publish_date = timestamp(?) and id < ?) or publish_date < timestamp(?)) and type = 'post' order by publish_date desc, id desc limit 1";
  $prev_post = $app['db']->fetchAssoc($prev_sql, array($publish_date, $current_id, $publish_date));

  if ($current_id == $prev_post['id']) {
    $prev_post['slug'] = 0;
  }

  $next_sql = "select id, title, slug from posts where status = 'published' and ((publish_date = timestamp(?) and id > ?) or publish_date > timestamp(?)) and type = 'post' order by publish_date, id limit 1";
  $next_post = $app['db']->fetchAssoc($next_sql, array($publish_date, $current_id, $publish_date));

  if ($current_id == $next_post['id']) {
    $next_post['slug'] = 0;
  }

  $post_nav = array(
    'prev' => $prev_post['slug'],
    'prev_title' => $prev_post['title'],
    'next' => $next_post['slug'],
    'next_title' => $next_post['title']
  );

  return $post_nav;
}

function getPosts($app, $current = 0, $total = 0, $query = '') {
  $per_page = $query ? $app['conf']['search_results_per_page'] : $app['conf']['posts_per_page'];
  $sql = '';
  $offset = 0;

  if ($current < 0) {
    $current = 1;
  }

  if ($current > 0 && $current < $total) {
    $offset = ($total - $current) * $per_page;
  }

  if (!$query) {
    $sql = "select * from posts where status = 'published' and type = 'post' order by publish_date desc, id desc limit $offset, $per_page";
  } else {
    $keywords = explode(' ', $query);
    $keywords = array_map(function($keyword) {
      return '\'%' . $keyword . '%\'';
    }, $keywords);
    $title_likes = implode(' OR title like ', $keywords);
    $content_likes = implode(' OR content like ', $keywords);

    $sql = "select * from posts where status = 'published' and ((title like $title_likes) or (content like $content_likes)) order by publish_date desc, type desc, id desc limit $offset, $per_page";
  }

  $pages = $app['db']->fetchAll($sql);

  return $pages;
}

function getPostsNav($app, $current = 0, $query = '') {
  $per_page = $query ? $app['conf']['search_results_per_page'] : $app['conf']['posts_per_page'];
  $prev = 0;
  $next = 0;

  if (!$query) {
    $sql = "select count(*) from posts where status = 'published' and type = 'post'";
  } else {
    $sql = "select count(*) from posts where status = 'published' and (title like '%$query%' or content like '%$query%')";
  }
  $total = $app['db']->fetchColumn($sql);

  $raw_total = (int) $total;
  $total = (int) ceil($total / $per_page);

  if ($current == 0 || $current > $total) {
    $current = $total;
  }

  if ($current < 0) {
    $current = 1;
  }

  if ($current > 1) {
    $prev = $current - 1;
  }

  if ($current + 1 <= $total) {
    $next = $current + 1;
  }

  if (!$prev && !$next) {
    $total = 0;
  }

  $posts_nav = array(
    'total' => $total,
    'current' => $current,
    'prev' => $prev,
    'next' => $next,
    'raw_total' => $raw_total
  );

  return $posts_nav;
}

function insertIntoPosts($app, $data) {
  $status = $app['db']->insert('posts', array(
    'title' => $data['title'],
    'slug' => $data['slug'],
    'content' => $data['content'],
    'type' => $data['type'],
    'main_menu' => $data['main_menu'] ? 1 : 0,
    'seq' => $data['seq'] ? $data['seq'] : 0,
    'status' => $data['status'],
    'publish_date' => date('Y-m-d H:i:s', strtotime($data['publish_date']))
  ));

  return $status;
}

function updatePost($app, $data) {
  $status = $app['db']->update('posts', array(
    'title' => $data['title'],
    'content' => $data['content'],
    'type' => $data['type'],
    'main_menu' => $data['main_menu'] ? 1 : 0,
    'seq' => $data['seq'] ? $data['seq'] : 0,
    'status' => $data['status'],
    'publish_date' => date('Y-m-d H:i:s', strtotime($data['publish_date']))
  ), array(
    'slug' => $data['slug'],
  ));

  return $status;
}
