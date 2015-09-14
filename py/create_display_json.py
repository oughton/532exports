import argparse
import json
import os
import logging

import shipping
import country

def find_closest_port(shipping_nodes, country):
    return shipping.get_nearest_port(shipping_nodes, country.lat, country.lon)

def get_all_paths(shipping_nodes, countries, subject):
    paths = []
    from_node = find_closest_port(shipping_nodes, subject)

    for c in countries:
        if c.name == subject.name:
            continue

        # if c.name != 'Japan':
        #     continue

        print c.name

        to_node = find_closest_port(shipping_nodes, c)

        try:
            short_path = shipping.get_short_path(shipping_nodes, from_node['node'], to_node['node'])
        except:
            logging.warning('could not determine path for %s' % c.name)
            continue

        paths.append(short_path)

    return paths

def combine_nodes(all_paths):
    result = []
    nodes = {}

    for path in all_paths:
        for node in path:
            node_id = node['node']
            if node_id not in nodes:
                nodes[node_id] = node
                result.append(node)
            else:
                edges = nodes[node_id]['edges']
                for edge in node['edges']:
                    if edge not in edges:
                        edges.append(edge)

    for node_id in nodes:
        result.append(nodes[node_id])

    return result

def main():
    parser = argparse.ArgumentParser()
    parser.add_argument('-s', '--shipping')
    parser.add_argument('-c', '--countries')
    parser.add_argument('-o','--output', default='output.json')

    args = parser.parse_args()

    shipping_path = args.shipping
    countries_path = args.countries
    output_path = args.output

    if not os.path.exists(shipping_path):
        parser.error('Shipping path does not exist')
    if not os.path.exists(countries_path):
        parser.error('Countries path does not exist')

    data = shipping.read_json(shipping_path)
    nodes = data['nodes']

    countries = country.read_csv(countries_path)
    subject = None

    for c in countries:
        if c.name == 'New Zealand':
            subject = c
            break

    all_paths = get_all_paths(nodes, countries, subject)

    result = combine_nodes(all_paths)
    shipping.write_json(output_path, result)

if __name__ == "__main__":
    main()