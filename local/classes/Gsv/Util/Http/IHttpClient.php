<?php


    namespace Gsv\Util\Http;


    abstract class IHttpClient
    {
        public function __construct($bodyDefaultsToJson = null, $autoParseResponse = true)
        {
            $this->setBodyDefaultsToJson($bodyDefaultsToJson);
            $this->setAutoParseResponse($autoParseResponse);
        }

        public final function get(string $url, /*string|array*/ $query = [], array $headers = []) : array
        {
            return $this->call('GET', $url, $query, $headers, null);
        }

        public final function post(string $url, $body, /*string|array*/ $query = [], array $headers = []) : array
        {
            return $this->call('POST', $url, $query, $headers, $body);
        }

        public final function put(string $url, $body, /*string|array*/ $query = [], array $headers = []) : array
        {
            return $this->call('PUT', $url, $query, $headers, $body);
        }

        public final function update(string $url, $body, /*string|array*/ $query = [], array $headers = []) : array
        {
            return $this->call('UPDATE', $url, $query, $headers, $body);
        }

        public final function delete(string $url, $body = '', /*string|array*/ $query = [], array $headers = []) : array
        {
            return $this->call('DELETE', $url, $query, $headers, $body);
        }

        public final function head(string $url, /*string|array*/ $query = [], array $headers = []) : array
        {
            return $this->call('HEAD', $url, $query, $headers, null);
        }

        public final function patch(string $url, $body, /*string|array*/ $query = [], array $headers = []) : array
        {
            return $this->call('PATCH', $url, $query, $headers, $body);
        }

        public abstract function call(string $method, string $url, /*string|array*/ $query, array $headers, $body) : array;

        public abstract function getBodyDefaultsToJson() : ?bool;

        public abstract function setBodyDefaultsToJson(?bool $bodyDefaultsToJson);

        public abstract function getAutoParseResponse() : bool;

        public abstract function setAutoParseResponse(bool $autoParseResponse);

        /**
         * Build multipart/form-data
         * https://gist.github.com/ssigwart/83c394fb689d4797ffc3 Does it work?
         * @param array @$data Data
         * @param array @$files 1-D array of files where key is field name and value if file contents
         * @param string &$contentType Retun variable for content type
         *
         * @return string Encoded data
         */
        private static function buildMultipartFormData(array &$data, array &$files, &$contentType)
        {
            $eol = "\r\n";

            // Build test string
            $testStr = '';
            array_walk_recursive($data, function($item, $key) use (&$testStr, &$eol) {
                $testStr .= $key . $eol . $item . $eol;
            });

            // Get file content type and content.  Add to test string
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $fileContent = array();
            $fileContentTypes = array();
            foreach ($files as $key=>$filename)
            {
                $fileContent[$key] = file_get_contents($filename);
                $fileContentTypes[$key] = finfo_file($finfo, $filename);
                $testStr .= $key . $eol . $fileContentTypes[$key] . $eol. $fileContent[$key] . $eol;
            }
            finfo_close($finfo);

            // Find a boundary not present in the test string
            $boundaryLen = 6;
            $alpha = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
            do {
                $boundary = '--';
                for ($i = 0; $i < $boundaryLen; $i++)
                {
                    $c = rand(0, 61);
                    $boundary .= $alpha[$c];
                }

                // Check test string
                if (strpos($testStr, $boundary) !== false)
                {
                    $boundary = null;
                    $boundaryLen++;
                }
            } while ($boundary === null);
            unset($testStr);

            // Set content type
            $contentType = 'multipart/form-data, boundary='.$boundary;

            // Build data
            $rtn = '';
            $func = function($data, $baseKey = null) use (&$func, &$boundary, &$eol)
            {
                $rtn = '';
                foreach ($data as $key=>&$value)
                {
                    // Get key
                    $key = $baseKey === null ? $key : ($baseKey . '[' . $key . ']');

                    // Add data
                    if (is_array($value))
                        $rtn .= $func($value, $key);
                    else
                        $rtn .= '--' . $boundary . $eol . 'Content-Disposition: form-data; name="'.$key.'"' . $eol . $eol . $value . $eol;
                }

                return $rtn;
            };
            $rtn = $func($data);

            // Add files
            foreach ($files as $key=>$filename)
            {
                $rtn .= '--' . $boundary . $eol . 'Content-Disposition: file; name="'.$key.'"; filename="'.basename($filename).'"' . $eol
                    . 'Content-Type: ' . $fileContentTypes[$key] . $eol
                    . 'Content-Transfer-Encoding: binary' . $eol . $eol
                    . $fileContent[$key] . $eol;
            }

            return $rtn . '--' . $boundary . '--' . $eol;
        }

        public static function build_multipart_form_data(array $data, array $files = [])
        {
            $eol = "\r\n";

            // Build test string
            $testStr = '';
            array_walk_recursive($data, function($item, $key) use (&$testStr, &$eol) {
                $testStr .= $key . $eol . $item . $eol;
            });

            if($files)
            {
                // Get file content type and content.  Add to test string
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                foreach ($files as $key => &$filename)
                {
                    if(is_string($filename))
                    {
                        $filename = [
                            'content' => file_get_contents($filename),
                            'content-type' => finfo_file($finfo, $filename),
                            'filename' => basename($filename),
                        ];
                        $testStr .= $key . $eol . $filename['content-type'] . $eol . $filename['content'] . $eol;
                    }
                    elseif(is_array($filename))
                    {
                        if(is_string($filename['content']) && is_string($filename['content-type']) && is_string($filename['filename']))
                        {
                            $testStr .= $key . $eol . $filename['content-type'] . $eol . $filename['content'] . $eol;
                        }
                        else
                        {
                            unset($files[$key]);
                        }
                    }
                    else
                    {
                        unset($files[$key]);
                    }
                }
                unset($filename);
                finfo_close($finfo);
            }

            // Find a boundary not present in the test string
            $boundaryLen = 6;
            $alpha = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
            while(true)
            {
                $boundary = '--';
                for ($i = 0; $i < $boundaryLen; $i++)
                {
                    $c = mt_rand(0, strlen($alpha) - 1);
                    $boundary .= $alpha[$c];
                }

                // Check test string
                if (strpos($testStr, $boundary) === false)
                    break;

                $boundaryLen++;
            }
            unset($testStr);

            // Set content type
            $contentType = 'multipart/form-data; boundary='.$boundary;

            // Build data
            $func = function($data, $baseKey = null) use (&$func, &$boundary, &$eol)
            {
                $body = '';
                foreach ($data as $key=>&$value)
                {
                    // Get key
                    $key = $baseKey === null ? $key : ($baseKey . '[' . $key . ']');

                    // Add data
                    if (is_array($value))
                        $body .= $func($value, $key);
                    else
                        $body .= '--' . $boundary . $eol . 'Content-Disposition: form-data; name="'.$key.'"' . $eol . $eol . $value . $eol;
                }

                return $body;
            };
            $body = $func($data);

            // Add files
            foreach ($files as $key=>$file)
            {
                $body .= '--' . $boundary . $eol . 'Content-Disposition: file; name="'.$key.'"; filename="'.$file['filename'].'"' . $eol
                    . 'Content-Type: ' . $file['content-type'] . $eol
                    . 'Content-Transfer-Encoding: binary' . $eol . $eol
                    . $file['content'] . $eol;
            }

            $body .= '--' . $boundary . '--' . $eol;
            return [$contentType, $body, $boundary];
        }
    }