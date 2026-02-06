<?php
/**
 * Addon Name: Automatic Meta Tag Generator (SEO)
 */

add_hook('head_bottom', function() {
    // Access the $page variable defined in index.php
    global $page;

    // If there's no page content to analyze, exit
    if (empty($page['content'])) {
        return;
    }

    $content = $page['content'];

    // 1. Generate Meta Description
    $description = '';
    if (preg_match('/<p[^>]*>(.*?)<\/p>/is', $content, $matches)) {
        $description = strip_tags($matches[1]);
    } else {
        $description = strip_tags($content);
    }

    $description = trim($description);
    if (strlen($description) > 160) {
        $description = substr($description, 0, 157) . '...';
    }

    // 2. Generate Meta Keywords
    $stopwords = ['the','and','is','in','on','of','to','a','for','with','at','by','an','be','as','or','from','that','this','it','are','was','were','will','can','has','had','not','but','if','they','their','which','you','your','we','our','us','these','those'];
    
    $words = str_word_count(strip_tags($content), 1);
    $filteredWords = array_filter($words, function($word) use ($stopwords) {
        return !in_array(strtolower($word), $stopwords) && strlen($word) > 3;
    });

    $wordFrequencies = array_count_values($filteredWords);
    arsort($wordFrequencies);
    $keywords = array_keys(array_slice($wordFrequencies, 0, 10));
    $keywordsString = implode(', ', $keywords);

    // 3. Output the tags directly into the head
    echo "\n    \n";
    echo '    <meta name="description" content="' . htmlspecialchars($description, ENT_QUOTES, 'UTF-8') . '" />' . "\n";
    echo '    <meta name="keywords" content="' . htmlspecialchars($keywordsString, ENT_QUOTES, 'UTF-8') . '" />' . "\n";
});