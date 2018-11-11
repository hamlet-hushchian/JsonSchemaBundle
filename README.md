# JSON schema bundle
Using this module you can generate and validate JSON schema
# Installation 
To install this module clone it into your machine and run composer install

# Getting started
#### Yoy can create and validate schema throw the 3 different ways

- object oriented way


        // creation
            $property = new ObjectProperty();
            $property->addProperty((new StringProperty('field1'))->setRequired(true));
            $property->addProperty(new StringProperty('field2'));
            $property->addProperty(new StringProperty('field3'));
            echo json_encode($property->display());
    
        // validation
            $input = [
                'field1' => 'value1',
                'field2' => 'value2'
            ];
            $errors = $property->validate($input);
            foreach ($errors as $error) {
                echo $error['code'] . ": " . $error['message'] . ", path: " . $error['path'];
            }
            
            
- from array way


        // creation
            $property = new ObjectProperty();
            $property->fromArray([
                'type' => 'object',
                'properties' => [
                    'field1' => [
                        "type" => "string",
                        "required" => true
                    ]
                ]
            ]);
            echo json_encode($property->display());
    
        // validation
            $input = [
                'field1' => 'value1',
                'field2' => 'value2'
            ];
            $errors = $property->validate($input);
            foreach ($errors as $error) {
                echo $error['code'] . ": " . $error['message'] . ", path: " . $error['path'];
            }
            
            
- from YML config way
    
    
    
        // creation
            $builder = new JsonSchemaBuilder();
            $builder->addConfig(new YAMLConfig('/path/to/schema.yml'));
            echo json_encode($builder->generate());
    
        // validtaion
            $input = [
                'field1' => 'value1',
                'field2' => 'value2'
            ];
            $errors = $builder->getRoot()->validate($input);
            foreach ($errors as $error) {
                echo $error['code'] . ": " . $error['message'] . ", path: " . $error['path'];
            }