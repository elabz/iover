<?php
namespace IOVER\API;

class Client {
    private $active_api;
    private $api_key;
    private $api_url;

    public function __construct() {
        $this->active_api = get_option('iover_active_api', 'openai');
        $this->api_key = get_option('iover_api_key_' . $this->active_api);
        
        if ($this->active_api === 'ollama') {
            $this->api_url = get_option('iover_api_url_ollama', 'http://localhost:11434/api/generate');
        }
    }

    public function process_image($image_path, $prompt) {
        if (!file_exists($image_path)) {
            throw new \Exception(__('Image file not found.', 'iover'));
        }

        switch ($this->active_api) {
            case 'openai':
                return $this->process_with_openai($image_path, $prompt);
            case 'anthropic':
                return $this->process_with_anthropic($image_path, $prompt);
            case 'ollama':
                return $this->process_with_ollama($image_path, $prompt);
            default:
                throw new \Exception(__('Invalid API provider selected.', 'iover'));
        }
    }

    private function process_with_openai($image_path, $prompt) {
        if (empty($this->api_key)) {
            throw new \Exception(__('OpenAI API key is not configured.', 'iover'));
        }

        $image_data = base64_encode(file_get_contents($image_path));
        
        $payload = array(
            'model' => 'gpt-4-vision-preview',
            'messages' => array(
                array(
                    'role' => 'system',
                    'content' => $prompt
                ),
                array(
                    'role' => 'user',
                    'content' => array(
                        array(
                            'type' => 'image_url',
                            'image_url' => array(
                                'url' => "data:image/jpeg;base64,{$image_data}"
                            )
                        )
                    )
                )
            ),
            'max_tokens' => 500
        );

        $response = wp_remote_post('https://api.openai.com/v1/chat/completions', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type' => 'application/json',
            ),
            'body' => json_encode($payload),
            'timeout' => 30,
        ));

        if (is_wp_error($response)) {
            throw new \Exception($response->get_error_message());
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (isset($body['error'])) {
            throw new \Exception($body['error']['message']);
        }

        return $this->parse_response($body);
    }

    private function process_with_anthropic($image_path, $prompt) {
        if (empty($this->api_key)) {
            throw new \Exception(__('Anthropic API key is not configured.', 'iover'));
        }

        $image_data = base64_encode(file_get_contents($image_path));
        
        $payload = array(
            'model' => 'claude-3-opus-20240229',
            'messages' => array(
                array(
                    'role' => 'user',
                    'content' => array(
                        array(
                            'type' => 'image',
                            'source' => array(
                                'type' => 'base64',
                                'media_type' => 'image/jpeg',
                                'data' => $image_data
                            )
                        ),
                        array(
                            'type' => 'text',
                            'text' => $prompt
                        )
                    )
                )
            ),
            'max_tokens' => 500
        );

        $response = wp_remote_post('https://api.anthropic.com/v1/messages', array(
            'headers' => array(
                'x-api-key' => $this->api_key,
                'anthropic-version' => '2023-06-01',
                'Content-Type' => 'application/json',
            ),
            'body' => json_encode($payload),
            'timeout' => 30,
        ));

        if (is_wp_error($response)) {
            throw new \Exception($response->get_error_message());
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (isset($body['error'])) {
            throw new \Exception($body['error']['message']);
        }

        return $this->parse_response($body);
    }

    private function process_with_ollama($image_path, $prompt) {
        if (empty($this->api_url)) {
            throw new \Exception(__('Ollama API URL is not configured.', 'iover'));
        }

        $image_data = base64_encode(file_get_contents($image_path));
        $model = get_option('iover_ollama_model', 'llava');
        
        $payload = array(
            'model' => $model,
            'prompt' => $prompt,
            'images' => array($image_data),
            'stream' => false
        );

        $response = wp_remote_post($this->api_url, array(
            'headers' => array(
                'Content-Type' => 'application/json',
            ),
            'body' => json_encode($payload),
            'timeout' => 30,
        ));

        if (is_wp_error($response)) {
            throw new \Exception($response->get_error_message());
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (isset($body['error'])) {
            throw new \Exception($body['error']);
        }

        return $this->parse_response($body);
    }

    private function parse_response($response) {
        $content = '';
        
        switch ($this->active_api) {
            case 'openai':
                $content = $response['choices'][0]['message']['content'];
                break;
            case 'anthropic':
                $content = $response['content'][0]['text'];
                break;
            case 'ollama':
                $content = $response['response'];
                break;
        }

        // Check if the response indicates the image doesn't contain required objects
        if (stripos($content, 'does not contain') !== false || 
            stripos($content, 'no required objects') !== false ||
            stripos($content, 'cannot find') !== false) {
            return array(
                'error' => __('Image does not contain necessary objects.', 'iover')
            );
        }

        // Parse the response into a Q&A format
        $qa_pairs = $this->extract_qa_pairs($content);
        
        return array(
            'success' => true,
            'qa_pairs' => $qa_pairs
        );
    }

    private function extract_qa_pairs($content) {
        $qa_pairs = array();
        
        // Split content into lines
        $lines = explode("\n", $content);
        
        $current_question = '';
        $current_answer = '';
        
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;
            
            // Check if line starts with Q: or Question:
            if (preg_match('/^(?:Q:|Question:)\s*(.+)/i', $line, $matches)) {
                // If we have a previous Q&A pair, save it
                if (!empty($current_question)) {
                    $qa_pairs[] = array(
                        'question' => $current_question,
                        'answer' => $current_answer
                    );
                }
                $current_question = $matches[1];
                $current_answer = '';
            }
            // Check if line starts with A: or Answer:
            elseif (preg_match('/^(?:A:|Answer:)\s*(.+)/i', $line, $matches)) {
                $current_answer = $matches[1];
            }
            // If we have a question but no answer pattern match, append to answer
            elseif (!empty($current_question)) {
                $current_answer .= ' ' . $line;
            }
        }
        
        // Add the last Q&A pair if exists
        if (!empty($current_question)) {
            $qa_pairs[] = array(
                'question' => $current_question,
                'answer' => $current_answer
            );
        }
        
        // If no Q&A pairs were found, create a single pair with the entire content
        if (empty($qa_pairs)) {
            $qa_pairs[] = array(
                'question' => __('AI Analysis', 'iover'),
                'answer' => $content
            );
        }
        
        return $qa_pairs;
    }
}
