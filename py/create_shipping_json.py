import argparse
import os
import json
from xml.etree import ElementTree

def _node_key(lat, lon):
    return lat + ',' + lon

def run(input_path, output_path):
    root = ElementTree.parse(input_path).getroot()

    node_lookup = {}

    all_paths = []

    nodes = []

    node_index = 0

    for entry_elem in root.findall('entry'):
        from_node = entry_elem.find('from_node').text[:-2]
        to_node = entry_elem.find('to_node').text[:-2]
        route_freq = entry_elem.find('route_frequency').text[0:-2]

        line = entry_elem.find('line').text
        coords = line.split(' ')

        path = []

        for i in range(0, len(coords), 2):
            lat = coords[i]
            lon = coords[i + 1]

            path.append([lat, lon])

            node_key = _node_key(lat, lon)

            if node_key not in node_lookup:
                node = {
                    'node': str(node_index),
                    'lat': float(lat),
                    'lon': float(lon),
                    'edges': []
                }

                node_lookup[node_key] = node
                nodes.append(node)

                node_index += 1

        all_paths.append(path)

    for path in all_paths:
        path_count = len(path)
        if path_count < 2:
            raise ValueError('Path count to small')
        for i in range(path_count):
            if i + 2 <= len(path):
                from_coords = path[i]
                to_coords = path[i + 1]

                from_node = node_lookup[_node_key(from_coords[0], from_coords[1])]
                to_node = node_lookup[_node_key(to_coords[0], to_coords[1])]

                if to_node['node'] not in from_node['edges']:
                    from_node['edges'].append(to_node['node'])
                if from_node['node'] not in to_node['edges']:
                    to_node['edges'].append(from_node['node'])

    result = {
        'nodes': nodes,
    }

    with open(output_path, "w") as outfile:
        json.dump(result, outfile, indent=2)

def main():
    parser = argparse.ArgumentParser()
    parser.add_argument('input')
    parser.add_argument('-o','--output', default='output.json')

    args = parser.parse_args()

    input_path = args.input
    output_path = args.output

    if not os.path.exists(input_path):
        parser.error('Input path does not exist')

    run(input_path, output_path)

if __name__ == "__main__":
    main()