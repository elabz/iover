## 1. Overview

**Plugin Name**: Image Object Verification for Multimodal AI  
**Description**: A free WordPress plugin allowing registered users to upload images, verify that they contain only specified objects, and send the images (along with a system prompt) to a selected AI API (OpenAI, Anthropic, or Ollama) for multimodal inference. Responses are presented to the user in a structured Q&A format.

---

## 2. Goals and Key Features

1. **Access Control**  
   - The plugin’s form and functionality are only accessible to **registered, logged-in users**.
   
2. **Image Upload and Verification**  
   - Users can upload an image through a front-end form.
   - The image is sent to the chosen AI API (OpenAI, Anthropic, or Ollama) along with a system prompt for multimodal analysis.  
   - If the detected objects in the image do not match the prompt’s specified objects, a warning message (“Image does not contain necessary objects”) is displayed, and the form submission is rejected.

3. **System Prompt Integration**  
   - A default system prompt is defined by the site admin in the plugin settings.
   - The plugin can optionally allow end-users to **edit** the system prompt if enabled in the plugin settings.

4. **Plugin Settings**  
   - **Text Field for System Prompt**: Admin can set the default system prompt.
   - **Toggle to Enable/Disable Prompt Editing**: Admin can allow or disallow end-users to modify the prompt.
   - **API Key Fields**: Admin can store API keys for OpenAI, Anthropic, and Ollama in separate text fields.
   - **API URL Fields**: Admin can store API URLs for Ollama in separate text fields.
   - **API Selection**: A radio button to select one of the three APIs as the active/primary API for inference.

5. **Response Display**  
   - The response from the API is parsed and displayed to the user in a **question–answer table** format.

6. **Unit Tests**  
   - The plugin should include basic unit tests covering core functionalities (e.g., verifying image upload handling, prompt generation, API key usage, access control).

---

## 3. Functional Requirements

### 3.1 Access Control

- The plugin should verify if a user is logged in using `is_user_logged_in()`.  
- If not logged in, display a message such as “You must be logged in to use this feature” and prevent form submission.  
- If logged in, display the form for uploading images and the system prompt (if editing is allowed).

### 3.2 Front-End Form

The front-end form includes:
1. **Image Upload Field** (type="file")  
   - Restrict to image formats (e.g., `.jpg`, `.png`, `.jpeg`).  
   - Handle file size limits as per WordPress standards (using `wp_handle_upload` or similar).
2. **System Prompt Text Field** (optional based on admin settings)  
   - If editing is disabled, show the prompt as read-only or hide it entirely.
3. **Submit Button**  

Upon submission:
1. Validate the user session.  
2. Validate the uploaded file is an image.  
3. Retrieve the stored system prompt or the user-modified prompt.  
4. Format the request as per the chosen API’s specifications for multimodal inference.  
5. Send request to the chosen API (OpenAI, Anthropic, or Groq).

### 3.3 Image Verification Against Prompt

- The plugin must analyze the AI inference response to check whether the image contains only the objects specified by the prompt.  
- The logic for verifying image contents could be:  
  1. Parse the AI’s structured response (e.g., JSON or text that includes a list of recognized objects).  
  2. Compare the recognized objects with the objects listed in the system prompt.  
  3. If they match or contain a subset that aligns with the prompt’s rules, continue.  
  4. If they do not match or contain extra objects, return “Image does not contain necessary objects” error.  

> **Note**: The exact details of how the objects are matched depend on the specific AI model’s output structure. The plugin must interpret the API’s response carefully.

### 3.4 Response Display

- If the image is verified as valid:
  1. Parse the API’s response to extract a structured set of questions and answers (the exact parsing will depend on the AI model’s output format).  
  2. Display a table with each question in one column and the corresponding answer in another.  
  3. If the API response is unstructured, the plugin might define a minimal, consistent format or fallback to a “Response” heading with text.  

- If the image fails verification:
  - Display the warning message “Image does not contain necessary objects.”

---

## 4. Plugin Settings

In the WordPress admin dashboard (e.g., via **Settings → Image Object Verification**), the plugin should provide:

1. **System Prompt Field**  
   - Text area or input field that allows the admin to set the default system prompt (e.g., “Describe only if the image contains a cat and a bowl. Ignore other objects.”).

2. **Toggle for Prompt Editing**  
   - A checkbox or toggle switch: “Allow users to edit system prompt.”  
   - When enabled, the text field is displayed on the front-end form. When disabled, the text field is hidden or read-only.

3. **API Key Fields**  
   - Three separate fields to store API keys:  
     - **OpenAI API Key**  
     - **Anthropic API Key**  
     - **Groq API Key**  

4. **API Selection Radio Button**  
   - Options for “OpenAI / Anthropic / Groq”.  
   - Only one can be active at a time.  
   - Determines which API endpoint the plugin will call on form submission.

5. **Save Settings**  
   - A “Save Changes” button to update plugin settings in the WordPress options table.

---

## 5. Data Flow

1. **Initialization**  
   - On plugin activation, register the settings (system prompt, toggle for prompt editing, API keys, chosen API).
   - Load any necessary scripts (e.g., JavaScript for front-end form submission, if needed) and styles.

2. **Front-End Form Rendering**  
   - Shortcode or block to display the upload form.  
   - Check `is_user_logged_in()`; if not, show “Please log in” message.

3. **Form Submission**  
   1. **Client-Side**: The user selects an image, optionally edits the prompt (if allowed), and clicks “Submit.”  
   2. **Server-Side**:  
      - Validate nonce to prevent CSRF.  
      - Validate file type and size.  
      - Retrieve chosen API from plugin settings, as well as the corresponding API key.  
      - Construct the API request.  
      - Send the request to the selected AI service.  
      - Parse the response:
        - Check whether the image contains only the required objects (as specified by the system prompt).  
        - If verification fails, return an error.  
        - Otherwise, parse the Q&A from the response (or any structured data).  
   3. **Response Rendering**:
      - Show the user a question–answer table or an error message if verification failed.

---

## 6. Implementation Outline

### 6.1 File Structure

```
iover/
├─ iover.php        // Main plugin file
├─ includes/
│  ├─ class-iover-settings.php         // Plugin settings class
│  ├─ class-iover-admin.php            // Admin menu & settings page
│  ├─ class-iover-frontend.php         // Front-end rendering & form handling
│  └─ class-iover-api-client.php       // Handles API calls to OpenAI, Anthropic, or Groq
├─ tests/
│  ├─ test-plugin-activation.php       // Basic activation tests
│  ├─ test-api-calls.php               // Unit tests for API calls
│  ├─ test-access-control.php          // Tests for login checks
│  └─ ...
└─ readme.txt                          // Description & instructions for WP.org
```

### 6.2 Main Plugin File (iover.php)

- Plugin header (Name, URI, Description, Version, Author, License).
- `register_activation_hook()` to initialize default settings (if needed).
- `add_action('admin_menu', 'iover_register_settings_page')` to add the settings page.
- `add_shortcode('iover_uploader', 'iover_render_frontend_form')` or a block-based approach to display the form in posts/pages.
- Enqueue scripts (if needed) for form validation.

### 6.3 Settings and Admin Menu

- A class (`class-iover-admin.php`) that registers settings using WordPress Settings API.  
- A function to create a menu item under **Settings** in the WP Admin.  
- Register each setting option:  
  - `iover_system_prompt`  
  - `iover_allow_prompt_edit`  
  - `iover_api_key_openai`  
  - `iover_api_key_anthropic`  
  - `iover_api_key_groq`  
  - `iover_active_api` (radio button)

### 6.4 Front-End Logic

- A class (`class-iover-frontend.php`) that handles:
  - Display of the form (including the optional prompt editing field).
  - Processing of `$_POST` / `$_FILES` data on submission, verifying user login, and nonce checks.
  - Calls to `class-iover-api-client.php` to handle the API request.

### 6.5 API Client

- `class-iover-api-client.php` with methods such as:
  - `setActiveApi($api_name)`: Sets the active API (OpenAI, Anthropic, or Groq).
  - `setApiKey($key)`: Sets the active API key.
  - `sendMultimodalRequest($imagePath, $prompt)`: Prepares request data (including prompt) and sends it to the active API endpoint.  
  - `parseResponse($apiResponse)`: Convert the raw response into a structured format (e.g., JSON with recognized objects, Q&A segments, etc.).

#### 6.5.1 Sample OpenAI API request
(direct request)
```
curl https://api.openai.com/v1/chat/completions \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $OPENAI_API_KEY" \
  -d '{
     "model": "gpt-4o-mini",
     "messages": [{"role": "user", "content": "Say this is a test!"}],
     "temperature": 0.7
   }'

```
 (Python code)
```
import base64
from openai import OpenAI

client = OpenAI()

# Function to encode the image
def encode_image(image_path):
    with open(image_path, "rb") as image_file:
        return base64.b64encode(image_file.read()).decode("utf-8")


# Path to your image
image_path = "path_to_your_image.jpg"

# Getting the base64 string
base64_image = encode_image(image_path)

response = client.chat.completions.create(
    model="gpt-4o-mini",
    messages=[
        {
            "role": "user",
            "content": [
                {
                    "type": "text",
                    "text": "What is in this image?",
                },
                {
                    "type": "image_url",
                    "image_url": {"url": f"data:image/jpeg;base64,{base64_image}"},
                },
            ],
        }
    ],
)

print(response.choices[0])

```

#### 6.5.2 Sample Anthropic API request 
(direct request)

```
curl https://api.anthropic.com/v1/messages \
     --header "x-api-key: $ANTHROPIC_API_KEY" \
     --header "anthropic-version: 2023-06-01" \
     --header "content-type: application/json" \
     --data \
'{
    "model": "claude-3-5-sonnet-20241022",
    "max_tokens": 1024,
    "messages": [
        {"role": "user", "content": "Hello, world"}
    ]
}'

```


(Python code)
```
import anthropic

client = anthropic.Anthropic()
message = client.messages.create(
    model="claude-3-5-sonnet-20241022",
    max_tokens=1024,
    messages=[
        {
            "role": "user",
            "content": [
                {
                    "type": "image",
                    "source": {
                        "type": "base64",
                        "media_type": image1_media_type,
                        "data": image1_data,
                    },
                },
                {
                    "type": "text",
                    "text": "Describe this image."
                }
            ],
        }
    ],
)
print(message)

```

#### 6.5.3 Sample Ollama API request

(Python code)
```
import base64
import requests
from PIL import Image

SYSTEM_PROMPT = """What is in this image?"""
def encode_image_to_base64(image_path):
    """Convert an image file to a base64 encoded string."""
    with open(image_path, "rb") as image_file:
        return base64.b64encode(image_file.read()).decode('utf-8')
def perform_ocr(image_path):
    """Perform OCR on the given image using Llama 3.2-Vision."""
    base64_image = encode_image_to_base64(image_path)
    response = requests.post(
        "<http://localhost:8080/chat>",  # Ensure this URL matches your Ollama service endpoint
        json={
            "model": "llama3.2-vision",
            "messages": [
                {
                    "role": "user",
                    "content": SYSTEM_PROMPT,
                    "images": [base64_image],
                },
            ],
        }
    )
    if response.status_code == 200:
        return response.json().get("message", {}).get("content", "")
    else:
        print("Error:", response.status_code, response.text)
        return None
if __name__ == "__main__":
    image_path = "path/to/your/image.jpg"  # Replace with your image path
    result = perform_ocr(image_path)
    if result:
        print("OCR Recognition Result:")
        print(result)
```


### 6.6 Image Verification

- After receiving the structured response:
  - Compare the list of recognized objects against the list in the system prompt.  
  - If mismatch, return an error status.  
  - Else, proceed.

### 6.7 Rendering the Q&A Table

- Once the plugin has the final structured data, it will render a table in HTML:
  ```html
  <table class="iover-response">
    <thead>
      <tr><th>Question</th><th>Answer</th></tr>
    </thead>
    <tbody>
      <!-- Loop through Q&A pairs -->
      <tr><td>{{question}}</td><td>{{answer}}</td></tr>
      ...
    </tbody>
  </table>
  ```

---

## 7. Security Considerations

1. **Nonce and CSRF**  
   - Use `wp_create_nonce()` and `check_admin_referer()` to protect form submissions.
2. **File Upload**  
   - Use WordPress functions (`wp_handle_upload`) to ensure safe handling and storage of uploaded images.
   - Validate MIME type to accept only images.
3. **API Keys**  
   - Use WordPress options API to securely store and retrieve keys.  
   - Mark them as private or hidden in the settings form if appropriate.  
   - Never expose them in front-end source code.

---

## 8. Unit Testing

A minimal testing approach could include:

1. **Plugin Activation Test**  
   - Check that plugin activation does not produce errors.  
   - Verify default options are set correctly.

2. **Access Control Test**  
   - Test the front-end form is inaccessible to users who are not logged in.

3. **API Key Retrieval Test**  
   - Ensure correct retrieval of the stored API key based on the active API.

4. **Prompt Handling Test**  
   - If editing is disabled, ensure the user-provided prompt is ignored or not displayed.  
   - If enabled, ensure it overrides the default prompt.

5. **API Call and Response Parsing Test**  
   - Mock an API response and ensure the plugin’s verification function identifies correct vs. incorrect object sets.  
   - Verify the Q&A table is generated properly from a sample structured response.

6. **Error Handling**  
   - Confirm that invalid file uploads are rejected.  
   - Confirm that an error is shown if the image does not contain the necessary objects.

---

## 9. Performance Considerations

- Large image uploads could impact performance. Do not allow files larger than 3Mb.  
- API requests should run asynchronously if possible, or at least provide user feedback (e.g., “Processing…”).

---

## 10. Maintenance and Extensibility

- Keep the code modular, with separate classes handling admin settings, front-end rendering, and API calls.  
- Provide hooks and filters for developers who may want to extend or override behavior.

---

## 11. Summary

This plugin specification ensures that only logged-in users can upload images, checks the image against a system prompt using an AI service (OpenAI, Anthropic, or Groq), and displays structured responses. It includes an admin interface for customizing prompts, selecting the API, storing keys, and controlling prompt editing. Basic unit tests and security measures round out the solution. 

This completes the technical specification for the **Image Object Verification for Multimodal AI** plugin.